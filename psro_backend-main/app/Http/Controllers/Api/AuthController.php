<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'captcha_id' => ['required', 'uuid'],
            'captcha_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        $captchaKey = CaptchaController::cacheKey($request->string('captcha_id')->toString());
        $expected = Cache::get($captchaKey);
        if ($expected === null) {
            throw ValidationException::withMessages([
                'captcha_code' => ['Invalid or expired verification code. Please try again.'],
            ]);
        }
        if ($expected !== $request->string('captcha_code')->toString()) {
            throw ValidationException::withMessages([
                'captcha_code' => ['Invalid or expired verification code. Please try again.'],
            ]);
        }
        Cache::forget($captchaKey);

        $key = Str::transliterate(Str::lower($request->string('email')->toString()).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])],
            ]);
        }

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($key);
            $emailLower = Str::lower($request->string('email')->toString());
            AuditLogService::record(
                $request,
                'auth.login_failed',
                null,
                null,
                null,
                'Invalid credentials',
                ['email_fingerprint' => hash('sha256', $emailLower)],
                'failure'
            );
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }
        RateLimiter::clear($key);

        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            $tempToken = Str::random(64);
            Cache::put('2fa_pending:'.$tempToken, $user->id, 300);

            return response()->json([
                'requires_2fa' => true,
                'temp_token' => $tempToken,
                'message' => 'Two-factor authentication is required.',
            ]);
        }

        $token = $user->createToken('auth')->plainTextToken;

        AuditLogService::record(
            $request,
            'auth.login',
            $user->id,
            null,
            null,
            'Signed in',
            [],
            'success'
        );

        $userData = $user->only('id', 'name', 'username', 'first_name', 'last_name', 'email', 'role');
        $userData['avatar_url'] = $user->avatar_url;
        $userData['two_factor_enabled'] = $user->hasTwoFactorEnabled();

        return response()->json([
            'user' => $userData,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        $userData = $user->only('id', 'name', 'username', 'first_name', 'last_name', 'email', 'role');
        $userData['avatar_url'] = $user->avatar_url;

        return response()->json([
            'user' => $userData,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $request->user()->currentAccessToken()->delete();

        AuditLogService::record(
            $request,
            'auth.logout',
            $userId,
            null,
            null,
            'Signed out',
            [],
            'success'
        );

        return response()->json(['message' => 'Logged out'], 200);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $userData = $user->only('id', 'name', 'username', 'first_name', 'last_name', 'email', 'role');
        $userData['avatar_url'] = $user->avatar_url;
        $userData['two_factor_enabled'] = $user->hasTwoFactorEnabled();

        return response()->json([
            'user' => $userData,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = \Illuminate\Support\Facades\Password::sendResetLink($request->only('email'));

        if ($status !== \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => __($status)]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => __($status)]);
    }
}
