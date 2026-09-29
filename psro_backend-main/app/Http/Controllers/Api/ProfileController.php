<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Verify current password for admin idle lock unlock (no captcha; requires valid session).
     */
    public function verifyUnlockPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->string('password')->toString(), $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => [trans('auth.failed')],
            ]);
        }

        return response()->json(['message' => 'Unlocked']);
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $userData = $user->only('id', 'name', 'username', 'first_name', 'last_name', 'nickname', 'email', 'website', 'bio', 'avatar_path', 'role', 'created_at');
        $userData['avatar_url'] = $this->resolveAvatarUrl($user);

        return response()->json([
            'user' => $userData,
        ]);
    }

    private function resolveAvatarUrl($user): ?string
    {
        if ($user->avatar_path) {
            return url('/api/storage/' . ltrim($user->avatar_path, '/'));
        }
        if ($user->email) {
            $hash = md5(strtolower(trim($user->email)));
            return "https://www.gravatar.com/avatar/{$hash}?s=96&d=mp";
        }
        return null;
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar_path = $path;
        $user->save();

        $userData = $user->only('id', 'name', 'username', 'first_name', 'last_name', 'nickname', 'email', 'website', 'bio', 'avatar_path', 'role', 'created_at');
        $userData['avatar_url'] = $this->resolveAvatarUrl($user);

        return response()->json([
            'user' => $userData,
            'message' => 'Profile picture updated.',
        ]);
    }

    public function removeAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        $userData = $user->only('id', 'name', 'username', 'first_name', 'last_name', 'nickname', 'email', 'website', 'bio', 'avatar_path', 'role', 'created_at');
        $userData['avatar_url'] = $this->resolveAvatarUrl($user);

        return response()->json([
            'user' => $userData,
            'message' => 'Profile picture removed.',
        ]);
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        unset($validated['current_password']);
        $user->fill($validated);
        // Keep name in sync with first_name + last_name
        $full = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $user->name = $full !== '' ? $full : $user->name;
        $user->save();

        $userData = $user->only('id', 'name', 'username', 'first_name', 'last_name', 'nickname', 'email', 'website', 'bio', 'avatar_path', 'role', 'created_at');
        $userData['avatar_url'] = $this->resolveAvatarUrl($user);

        return response()->json([
            'user' => $userData,
            'message' => 'Profile updated.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $request->user()->currentAccessToken()->delete();
        Auth::logout();
        $user->delete();

        return response()->json(['message' => 'Account deleted.'], 200);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Password updated.']);
    }
}
