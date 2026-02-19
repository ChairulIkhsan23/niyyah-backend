<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="Register new user",
     *     description="Mendaftarkan user baru dan mengembalikan token akses",
     *     operationId="register",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Data user baru",
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation","city"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="Nama lengkap"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email user"),
     *             @OA\Property(property="password", type="string", format="password", example="123456", description="Password minimal 6 karakter"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="123456", description="Konfirmasi password"),
     *             @OA\Property(property="city", type="string", example="Bandung", description="Kota domisili"),
     *             @OA\Property(property="timezone", type="string", example="Asia/Jakarta", default="Asia/Jakarta", description="Zona waktu"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="Jenis kelamin (opsional)"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="2005-03-21", description="Tanggal lahir (opsional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registrasi berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="city", type="string", example="Bandung"),
     *                     @OA\Property(property="timezone", type="string", example="Asia/Jakarta")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="1|laravel_sanctum_token_here"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
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
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken."))
     *             )
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'city' => 'required|string|max:100',
            'timezone' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date|before:today',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'city' => $request->city,
            'timezone' => $request->timezone ?? 'Asia/Jakarta',
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Login user",
     *     description="Autentikasi user dan mengembalikan token akses",
     *     operationId="login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="access_token", type="string", example="1|laravel_sanctum_token_here"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Kredensial salah",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The provided credentials are incorrect.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        $deviceName = $request->userAgent() ?? 'mobile';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Logout user",
     *     description="Menghapus token akses saat ini",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Get authenticated user",
     *     description="Mengambil data user yang sedang login",
     *     operationId="me",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Data user berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'data' => $request->user()
        ], 200);
    }
}