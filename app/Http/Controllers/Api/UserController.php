<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'data' => $request->user()
        ], 200);
    }

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        
        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->has('city')) {
            $data['city'] = $request->city;
        }
        
        $data['timezone'] = $request->timezone ?? 'Asia/Jakarta';
        
        if ($request->has('gender')) {
            $data['gender'] = $request->gender;
        }
        
        if ($request->has('date_of_birth')) {
            $data['date_of_birth'] = $request->date_of_birth;
        }
        
        if ($request->has('bio')) {
            $data['bio'] = $request->bio;
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ], 200);
    }

    /**
     * Update user avatar
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $avatarPath = $request->file('avatar')->store('avatars', 'public');
        
        $user->update(['avatar' => $avatarPath]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated successfully',
            'data' => [
                'avatar_url' => asset('storage/' . $avatarPath)
            ]
        ], 200);
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();
        
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ], 200);
    }

    /**
     * Get user's devices/tokens
     */
    public function devices(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->orderBy('last_used_at', 'desc')
            ->get(['id', 'name', 'last_used_at', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $tokens
        ], 200);
    }

    /**
     * Revoke specific token/device
     */
    public function revokeDevice(Request $request, $tokenId)
    {
        $request->user()->tokens()
            ->where('id', $tokenId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device revoked successfully'
        ], 200);
    }

    /**
     * Logout from all devices
     */
    public function logoutAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices successfully'
        ], 200);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ], 200);
    }
}