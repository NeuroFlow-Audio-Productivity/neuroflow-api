<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\GoogleCallbackRequest;
use App\Http\Requests\Auth\GoogleRedirectRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationEmailRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Auth\GoogleRedirectResource;
use App\Http\Resources\Auth\LoginResource;
use App\Http\Resources\Auth\MessageResource;
use App\Http\Resources\Auth\ResetPasswordTokenResource;
use App\Http\Resources\Auth\UserPayloadResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Interfaces\IUserService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly IUserService $userService,
    ) {}

    /**
     * Register a new user and send the verification email.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): UserPayloadResource
    {
        $user = $this->userService->register($request->validated());

        return new UserPayloadResource(
            'Registration successful. Please verify your email address.',
            $user,
            Response::HTTP_CREATED,
        );
    }

    /**
     * Authenticate a verified user and issue a Sanctum access token.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): LoginResource
    {
        $payload = $this->userService->login($request->validated());

        return new LoginResource(
            'Login successful.',
            $payload['access_token'],
            $payload['token_type'],
            $payload['user'],
        );
    }

    /**
     * Generate the Google OAuth authorization URL.
     *
     * The frontend should redirect the user to the returned URL and may provide
     * its own redirect_uri and state values when they match the Google client settings.
     *
     * @unauthenticated
     */
    public function googleRedirect(GoogleRedirectRequest $request): GoogleRedirectResource
    {
        return new GoogleRedirectResource(
            'Google authorization URL generated successfully.',
            $this->userService->googleAuthorizationUrl($request->validated()),
        );
    }

    /**
     * Exchange a Google OAuth authorization code for a Sanctum access token.
     *
     * @unauthenticated
     */
    public function googleCallback(GoogleCallbackRequest $request): LoginResource
    {
        $payload = $this->userService->loginWithGoogle($request->validated());

        return new LoginResource(
            'Google login successful.',
            $payload['access_token'],
            $payload['token_type'],
            $payload['user'],
        );
    }

    /**
     * Resend the email verification notification.
     *
     * @unauthenticated
     */
    public function resendVerificationEmail(ResendVerificationEmailRequest $request): MessageResource
    {
        $this->userService->resendEmailVerification($request->validated('email'));

        return new MessageResource('Verification email sent successfully.');
    }

    /**
     * Verify the user's email from the signed verification link.
     *
     * @unauthenticated
     */
    public function verifyEmail(int $id, string $hash): UserPayloadResource
    {
        $user = $this->userService->verifyEmail($id, $hash);

        return new UserPayloadResource('Email verified successfully.', $user);
    }

    /**
     * Send a password reset link to the given email address.
     *
     * @unauthenticated
     */
    public function forgotPassword(ForgotPasswordRequest $request): MessageResource
    {
        $this->userService->sendPasswordResetLink($request->validated('email'));

        return new MessageResource('Password reset link sent successfully.');
    }

    /**
     * Return the reset password token payload for API clients.
     *
     * @unauthenticated
     */
    public function showResetPassword(Request $request, string $token): ResetPasswordTokenResource
    {
        return new ResetPasswordTokenResource(
            'Use this token and email address to complete the password reset.',
            $token,
            $request->query('email'),
        );
    }

    /**
     * Reset the user's password using a valid reset token.
     *
     * @unauthenticated
     */
    public function resetPassword(ResetPasswordRequest $request): MessageResource
    {
        $this->userService->resetPassword($request->validated());

        return new MessageResource('Password reset successfully.');
    }

    /**
     * Revoke the current Sanctum token.
     */
    public function logout(Request $request): MessageResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->userService->logout($user);

        return new MessageResource('Logout successful.');
    }

    /**
     * Get the currently authenticated user.
     */
    public function currentUser(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('profile');

        return new UserResource($user);
    }
}
