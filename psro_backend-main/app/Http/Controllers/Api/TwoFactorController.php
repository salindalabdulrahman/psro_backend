<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    protected function google2fa(): Google2FA
    {
        return app(Google2FA::class);
    }

    protected function handleEncryptionError(\Throwable $e): void
    {
        if ($e instanceof MissingAppKeyException) {
            throw ValidationException::withMessages([
                'code' => [
                    'Application encryption key is missing. Run "php artisan key:generate" in the psro-backend/psro-backend directory, then restart your server.',
                ],
            ]);
        }
        throw $e;
    }

    /**
     * Generate a new 2FA secret and return QR code URL for setup.
     */
    public function setup(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password')],
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled.',
                'enabled' => true,
            ], 400);
        }

        $secret = $this->google2fa()->generateSecretKey(32);
        $qrUrl = $this->google2fa()->getQRCodeUrl(
            config('app.name', 'PSRO-BARMM'),
            $user->email,
            $secret
        );

        // Store secret temporarily until confirmed (in session/cache keyed by user)
        Cache::put('2fa_setup:'.$user->id, $secret, 600);

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrUrl,
        ]);
    }

    /**
     * Confirm 2FA setup by verifying the first code.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $secret = Cache::get('2fa_setup:'.$user->id);

        if (! $secret) {
            throw ValidationException::withMessages([
                'code' => ['Setup session expired. Please start the setup process again.'],
            ]);
        }

        if (! $this->google2fa()->verifyKey($secret, $request->code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided code is invalid.'],
            ]);
        }

        try {
            $encryptedSecret = encrypt($secret);
        } catch (\Throwable $e) {
            $this->handleEncryptionError($e);
        }

        $user->forceFill([
            'two_factor_secret' => $encryptedSecret,
            'two_factor_confirmed_at' => now(),
        ])->save();

        Cache::forget('2fa_setup:'.$user->id);

        AuditLogService::record(
            $request,
            'two_factor.enabled',
            $user->id,
            null,
            null,
            'Two-factor authentication enabled',
            [],
            'success'
        );

        return response()->json([
            'message' => 'Two-factor authentication has been enabled.',
            'enabled' => true,
        ]);
    }

    /**
     * Disable 2FA (requires password).
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password')],
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLogService::record(
            $request,
            'two_factor.disabled',
            $user->id,
            null,
            null,
            'Two-factor authentication disabled',
            [],
            'success'
        );

        return response()->json([
            'message' => 'Two-factor authentication has been disabled.',
            'enabled' => false,
        ]);
    }

    /**
     * Verify 2FA code during login and exchange temp token for auth token.
     */
    public function challenge(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = Cache::get('2fa_pending:'.$request->temp_token);

        if (! $userId) {
            throw ValidationException::withMessages([
                'code' => ['Session expired. Please log in again.'],
            ]);
        }

        $user = \App\Models\User::find($userId);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            Cache::forget('2fa_pending:'.$request->temp_token);
            throw ValidationException::withMessages([
                'code' => ['Invalid session. Please log in again.'],
            ]);
        }

        try {
            $secret = decrypt($user->two_factor_secret);
        } catch (\Throwable $e) {
            $this->handleEncryptionError($e);
        }

        if (! $this->google2fa()->verifyKey($secret, $request->code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code was invalid.'],
            ]);
        }

        Cache::forget('2fa_pending:'.$request->temp_token);

        $token = $user->createToken('auth')->plainTextToken;

        AuditLogService::record(
            $request,
            'auth.login',
            $user->id,
            null,
            null,
            'Signed in (two-factor)',
            ['two_factor' => true],
            'success'
        );

        $userData = $user->only('id', 'name', 'username', 'first_name', 'last_name', 'email', 'role');
        $userData['avatar_url'] = $user->avatar_url;
        $userData['two_factor_enabled'] = true;

        return response()->json([
            'user' => $userData,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
