<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    /**
     * Login user and return token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // First, check if user exists to determine role-based validation
        $user = User::where('email', $request->email)->first();
        
        // Validate PUP institutional email domain (only for students/interns, not admins)
        if ($user && $user->role === 'student') {
            $allowedDomains = ['pup.edu.ph', 'iskolarngbayan.pup.edu.ph'];
            $emailDomain = substr(strrchr($request->email, '@'), 1);
            
            if (!in_array($emailDomain, $allowedDomains)) {
                return response()->json([
                    'message' => 'Only PUP institutional webmail accounts are allowed for students',
                ], 403);
            }
        }

        // Block unactivated student accounts (return this even if password is wrong)
        if ($user && $user->role === 'student' && !$user->is_activated) {
            return response()->json([
                'message' => 'Your account has not been activated yet. Please wait for your professor to confirm your registration, then check your email to set your password.',
            ], 403);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Revoke previous tokens if needed (optional - uncomment to allow single session only)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'email' => $user->email,
                'role' => $user->role,
                'student_id' => $user->student_id,
                'ojt_status' => $user->ojt_status,
            ],
        ], 200);
    }

    /**
     * Logout user (revoke current token).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'email' => $user->email,
                'role' => $user->role,
                'student_id' => $user->student_id,
                'ojt_status' => $user->ojt_status,
            ],
        ], 200);
    }

    /**
     * Send password reset link.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if user exists
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json([
                    'message' => 'If an account exists with this email, a password reset link will be sent.',
                ], 200);
            }

            // Block unactivated student accounts from resetting password
            if ($user->role === 'student' && !$user->is_activated) {
                return response()->json([
                    'message' => 'Your account has not been activated yet. Please wait for your professor to confirm your registration.',
                ], 403);
            }

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Password reset link sent to your email',
                ], 200);
            }

            Log::warning('Password reset link failed', [
                'status' => $status,
                'email' => $request->email,
            ]);

            return response()->json([
                'message' => 'Unable to send reset link. Please try again later.',
                'error' => config('app.debug') ? __($status) : null,
            ], 500);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database connection error during password reset: ' . $e->getMessage());

            return response()->json([
                'message' => 'Database connection error. Please check your database configuration.',
                'error' => config('app.debug') ? $e->getMessage() : 'Database unavailable',
            ], 500);
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            Log::error('Mail transport error during password reset: ' . $e->getMessage());

            return response()->json([
                'message' => 'Unable to send email. Please check mail configuration.',
                'error' => config('app.debug') ? $e->getMessage() : 'Mail service unavailable',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'email' => $request->email,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while sending the reset link. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reset password with token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Revoke all tokens after password reset
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Unable to reset password',
            'error' => __($status),
        ], 400);
    }
}
