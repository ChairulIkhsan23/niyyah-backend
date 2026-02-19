<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Validation\ValidationException;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/google",
     *     summary="Login/Register with Google",
     *     description="Authenticate user using Google ID Token from Flutter/Android app",
     *     operationId="googleSignIn",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_token"},
     *             @OA\Property(
     *                 property="id_token", 
     *                 type="string", 
     *                 description="Google ID Token from Android Google Sign-In",
     *                 example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjJ4YzNwZ..."
     *             ),
     *             @OA\Property(
     *                 property="city", 
     *                 type="string", 
     *                 description="User's city (optional)",
     *                 example="Jakarta"
     *             ),
     *             @OA\Property(
     *                 property="timezone", 
     *                 type="string", 
     *                 description="User's timezone (optional)",
     *                 example="Asia/Jakarta",
     *                 default="Asia/Jakarta"
     *             ),
     *             @OA\Property(
     *                 property="gender", 
     *                 type="string", 
     *                 enum={"male", "female"},
     *                 description="User's gender (optional)",
     *                 example="male"
     *             ),
     *             @OA\Property(
     *                 property="date_of_birth", 
     *                 type="string", 
     *                 format="date",
     *                 description="User's date of birth (optional)",
     *                 example="2000-01-01"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Google authentication successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Google login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="google_id", type="string", example="1234567890"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/..."),
     *                     @OA\Property(property="city", type="string", example="Jakarta"),
     *                     @OA\Property(property="timezone", type="string", example="Asia/Jakarta"),
     *                     @OA\Property(property="email_verified_at", type="string", format="datetime")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="1|laravel_sanctum_token_here"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Google authentication failed"),
     *             @OA\Property(property="error", type="string", example="Invalid token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id_token",
     *                     type="array",
     *                     @OA\Items(type="string", example="The id token field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function googleSignIn(Request $request): JsonResponse
    {
        // Validate request
        $validator = validator($request->all(), [
            'id_token' => 'required|string',
            'city' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date|before:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Log start of authentication
            Log::info('Google sign-in attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Verify Google ID token
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->id_token);

            if (!$googleUser || !$googleUser->getEmail()) {
                throw new Exception('Invalid Google token: No user data received');
            }

            // Log successful verification
            Log::info('Google token verified', [
                'google_id' => $googleUser->getId(),
                'email' => $googleUser->getEmail()
            ]);

            // Find or create user
            $user = $this->findOrCreateUser($googleUser, $request);

            // Create Sanctum token
            $token = $user->createToken(
                'google_auth_' . $request->userAgent(),
                ['*'],
                now()->addDays(30) // Token expires in 30 days
            )->plainTextToken;

            // Load fresh user instance
            $user->refresh();

            // Log successful login
            Log::info('Google sign-in successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Google login successful',
                'data' => [
                    'user' => $user->makeHidden(['password', 'remember_token']),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 30 * 24 * 60 * 60 // 30 days in seconds
                ]
            ], 200);

        } catch (\InvalidArgumentException $e) {
            // Configuration error
            Log::error('Google OAuth configuration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Google OAuth configuration error',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);

        } catch (ValidationException $e) {
            // Token validation failed
            Log::warning('Google token validation failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid Google token',
                'error' => config('app.debug') ? $e->getMessage() : 'Authentication failed'
            ], 401);

        } catch (Exception $e) {
            // General error
            Log::error('Google sign-in error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 401);
        }
    }

    /**
     * Find or create user from Google data
     *
     * @param object $googleUser
     * @param Request $request
     * @return User
     */
    private function findOrCreateUser($googleUser, Request $request): User
    {
        // Try to find user by google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            // Update existing user's avatar if needed
            if ($googleUser->getAvatar() && !$user->avatar) {
                $user->avatar = $googleUser->getAvatar();
                $user->save();
            }

            return $user;
        }

        // Try to find user by email
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link Google account to existing user
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $user->avatar ?? $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            Log::info('Google account linked to existing user', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return $user;
        }

        // Create new user
        $userData = [
            'name' => $googleUser->getName() ?? explode('@', $googleUser->getEmail())[0],
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password' => Hash::make(Str::random(24)),
            'email_verified_at' => now(),
            'avatar' => $googleUser->getAvatar(),
            'timezone' => $request->timezone ?? 'Asia/Jakarta',
            'city' => $request->city,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
        ];

        // Filter out null values
        $userData = array_filter($userData, function ($value) {
            return !is_null($value);
        });

        $user = User::create($userData);

        Log::info('New user created via Google sign-in', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return $user;
    }
}