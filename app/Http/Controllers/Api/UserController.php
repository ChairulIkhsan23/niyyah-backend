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
     * @OA\Get(
     *     path="/user/profile",
     *     summary="Get user profile",
     *     description="Mengambil data profil user yang sedang login",
     *     operationId="getProfile",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profil berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/user/profile",
     *     summary="Update user profile",
     *     description="Memperbarui data profil user",
     *     operationId="updateProfile",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string", example="John Updated"),
     *             @OA\Property(property="email", type="string", format="email", example="john.updated@example.com"),
     *             @OA\Property(property="city", type="string", example="Jakarta"),
     *             @OA\Property(property="timezone", type="string", example="Asia/Jakarta"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="2005-03-21"),
     *             @OA\Property(property="bio", type="string", example="Saya seorang developer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil berhasil diperbarui",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/user/avatar",
     *     summary="Update user avatar",
     *     description="Mengupload foto profil user",
     *     operationId="updateAvatar",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"avatar"},
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="File gambar (jpeg, png, jpg, gif) max 2MB"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar berhasil diupload",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Avatar updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="avatar_url", type="string", example="http://127.0.0.1:8000/storage/avatars/filename.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="avatar", type="array", @OA\Items(type="string", example="The avatar field must be an image."))
     *             )
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/user/password",
     *     summary="Update user password",
     *     description="Memperbarui password user",
     *     operationId="updatePassword",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="123456", description="Password saat ini"),
     *             @OA\Property(property="new_password", type="string", format="password", example="1234567", description="Password baru minimal 6 karakter"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="1234567", description="Konfirmasi password baru")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password berhasil diperbarui",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="current_password", type="array", @OA\Items(type="string", example="The current password is incorrect."))
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/user/devices",
     *     summary="Get user devices",
     *     description="Mengambil daftar perangkat/token yang sedang aktif",
     *     operationId="getDevices",
     *     tags={"Device Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar perangkat berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="PostmanRuntime/7.51.1"),
     *                     @OA\Property(property="last_used_at", type="string", format="datetime", example="2026-02-19T10:30:00.000000Z"),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2026-02-18T23:31:12.000000Z")
     *                 )
     *             )
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/user/devices/{tokenId}",
     *     summary="Revoke device",
     *     description="Menghapus token/perangkat tertentu",
     *     operationId="revokeDevice",
     *     tags={"Device Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="tokenId",
     *         in="path",
     *         required=true,
     *         description="ID token yang akan direvoke",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device berhasil direvoke",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Device revoked successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Token tidak ditemukan"
     *     )
     * )
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
     * @OA\Post(
     *     path="/user/logout-all",
     *     summary="Logout from all devices",
     *     description="Menghapus semua token (logout dari semua perangkat)",
     *     operationId="logoutAllDevices",
     *     tags={"Device Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil logout dari semua perangkat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out from all devices successfully")
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/user/account",
     *     summary="Delete user account",
     *     description="Menghapus akun user beserta semua data terkait",
     *     operationId="deleteAccount",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password"},
     *             @OA\Property(property="password", type="string", format="password", example="123456", description="Konfirmasi password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Akun berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account deleted successfully")
     *         )
     *     )
     * )
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