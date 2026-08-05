<?php

namespace Tests\Feature\Auth;

use App\Models\Profile;
use App\Models\User;
use Database\Seeders\ProfileSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProfileSeeder::class);
    }

    public function test_user_can_register_and_receive_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonPath('data.profile.slug', Profile::USER_SLUG);

        $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertSame(Profile::USER_SLUG, $user->profile->slug);
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
            $mailMessage = $notification->toMail($user);

            return str_starts_with(
                $mailMessage->actionUrl,
                Config::get('app.frontend_url')."/auth/email/verify/{$user->id}/".sha1($user->email),
            );
        });
    }

    public function test_verified_user_can_login_and_receive_a_sanctum_token(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123',
            'device_name' => 'postman',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.auth_provider', 'password')
            ->assertJsonPath('user.profile.slug', Profile::USER_SLUG)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'profile' => ['id', 'name', 'slug'], 'name', 'email', 'auth_provider'],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_google_redirect_returns_authorization_url(): void
    {
        Config::set('services.google.client_id', 'google-client-id');
        Config::set('services.google.redirect', 'http://localhost:5174/auth/google/callback');

        $response = $this->getJson('/api/auth/google/redirect?'.http_build_query([
            'redirect_uri' => 'http://localhost:5174/auth/google/callback',
            'state' => 'csrf-state-token',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Google authorization URL generated successfully.')
            ->assertJsonStructure(['message', 'authorization_url']);

        $authorizationUrl = $response->json('authorization_url');
        parse_str(parse_url($authorizationUrl, PHP_URL_QUERY) ?: '', $query);

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $authorizationUrl);
        $this->assertSame('google-client-id', $query['client_id'] ?? null);
        $this->assertSame('http://localhost:5174/auth/google/callback', $query['redirect_uri'] ?? null);
        $this->assertSame('code', $query['response_type'] ?? null);
        $this->assertSame('openid profile email', $query['scope'] ?? null);
        $this->assertSame('csrf-state-token', $query['state'] ?? null);
    }

    public function test_user_can_login_with_google_and_receive_a_sanctum_token(): void
    {
        Config::set('services.google.client_id', 'google-client-id');
        Config::set('services.google.client_secret', 'google-client-secret');
        Config::set('services.google.redirect', 'http://localhost:5174/auth/google/callback');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-user-123',
                'email' => 'ada@example.com',
                'email_verified' => true,
                'name' => 'Ada Lovelace',
                'picture' => 'https://example.com/avatar.png',
            ]),
        ]);

        $response = $this->postJson('/api/auth/google/callback', [
            'code' => 'valid-google-code',
            'redirect_uri' => 'http://localhost:5174/auth/google/callback',
            'device_name' => 'chrome',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Google login successful.')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'ada@example.com')
            ->assertJsonPath('user.auth_provider', 'google')
            ->assertJsonPath('user.google_avatar_url', 'https://example.com/avatar.png')
            ->assertJsonPath('user.profile.slug', Profile::USER_SLUG)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'profile' => ['id', 'name', 'slug'], 'name', 'email', 'auth_provider', 'google_avatar_url'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ada@example.com',
            'google_id' => 'google-user-123',
            'google_avatar_url' => 'https://example.com/avatar.png',
        ]);
        $this->assertNotNull(User::query()->where('email', 'ada@example.com')->firstOrFail()->email_verified_at);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_google_callback_redirects_browser_get_request_to_frontend_with_token(): void
    {
        Config::set('app.frontend_url', 'http://localhost:5174');
        Config::set('services.google.client_id', 'google-client-id');
        Config::set('services.google.client_secret', 'google-client-secret');
        Config::set('services.google.redirect', 'http://localhost/api/auth/google/callback');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-get-user-123',
                'email' => 'browser-callback@example.com',
                'email_verified' => true,
                'name' => 'Browser Callback',
                'picture' => 'https://example.com/browser.png',
            ]),
        ]);

        $response = $this->get('/api/auth/google/callback?'.http_build_query([
            'state' => '14255ecb2437533e3b0e2bccffbf9b74',
            'iss' => 'https://accounts.google.com',
            'code' => 'valid-google-code',
            'scope' => 'email profile openid',
            'authuser' => '0',
            'prompt' => 'consent',
        ]));

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringStartsWith('http://localhost:5174/auth/callback?', $location);

        parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $query);
        $this->assertNotEmpty($query['access_token'] ?? null);
        $this->assertSame('Bearer', $query['token_type'] ?? null);

        $this->assertDatabaseHas('users', [
            'email' => 'browser-callback@example.com',
            'google_id' => 'google-get-user-123',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_google_login_rejects_an_existing_password_user_by_email(): void
    {
        Config::set('services.google.client_id', 'google-client-id');
        Config::set('services.google.client_secret', 'google-client-secret');
        Config::set('services.google.redirect', 'http://localhost:5174/auth/google/callback');

        $user = User::factory()->unverified()->create([
            'email' => 'existing@example.com',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-existing-123',
                'email' => 'existing@example.com',
                'email_verified' => true,
                'name' => 'Existing User',
                'picture' => 'https://example.com/existing.png',
            ]),
        ]);

        $response = $this->postJson('/api/auth/google/callback', [
            'code' => 'valid-google-code',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'auth_provider'])
            ->assertJsonPath('errors.email.0', 'This email is registered with password login. Please sign in with email and password.')
            ->assertJsonPath('errors.auth_provider.0', 'password');

        $user->refresh();

        $this->assertNull($user->google_id);
        $this->assertNull($user->google_avatar_url);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_google_browser_callback_redirects_existing_password_user_to_login_error(): void
    {
        Config::set('app.frontend_url', 'http://localhost:5174');
        Config::set('services.google.client_id', 'google-client-id');
        Config::set('services.google.client_secret', 'google-client-secret');
        Config::set('services.google.redirect', 'http://localhost/api/auth/google/callback');

        $user = User::factory()->unverified()->create([
            'email' => 'existing-browser@example.com',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-existing-browser-123',
                'email' => 'existing-browser@example.com',
                'email_verified' => true,
                'name' => 'Existing Browser User',
            ]),
        ]);

        $response = $this->get('/api/auth/google/callback?'.http_build_query([
            'code' => 'valid-google-code',
        ]));

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringStartsWith('http://localhost:5174/login?', $location);

        parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $query);
        $this->assertSame('auth_provider_password', $query['error'] ?? null);
        $this->assertSame(
            'This email is registered with password login. Please sign in with email and password.',
            $query['message'] ?? null,
        );

        $user->refresh();

        $this->assertNull($user->google_id);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_google_user_can_continue_logging_in_with_google(): void
    {
        Config::set('services.google.client_id', 'google-client-id');
        Config::set('services.google.client_secret', 'google-client-secret');
        Config::set('services.google.redirect', 'http://localhost:5174/auth/google/callback');

        $user = User::factory()->create([
            'email' => 'google@example.com',
            'google_id' => 'google-existing-123',
            'google_avatar_url' => 'https://example.com/old.png',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-existing-123',
                'email' => 'google@example.com',
                'email_verified' => true,
                'name' => 'Existing Google User',
                'picture' => 'https://example.com/new.png',
            ]),
        ]);

        $response = $this->postJson('/api/auth/google/callback', [
            'code' => 'valid-google-code',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.auth_provider', 'google')
            ->assertJsonPath('user.google_avatar_url', 'https://example.com/new.png');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_google_login_rejects_unverified_google_email(): void
    {
        Config::set('services.google.client_id', 'google-client-id');
        Config::set('services.google.client_secret', 'google-client-secret');
        Config::set('services.google.redirect', 'http://localhost:5174/auth/google/callback');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-user-123',
                'email' => 'unverified@example.com',
                'email_verified' => false,
                'name' => 'Unverified User',
            ]),
        ]);

        $response = $this->postJson('/api/auth/google/callback', [
            'code' => 'valid-google-code',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('users', [
            'email' => 'unverified@example.com',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_google_user_cannot_login_with_password(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-user-123',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'auth_provider'])
            ->assertJsonPath('errors.email.0', 'This account uses Google sign-in. Please continue with Google.')
            ->assertJsonPath('errors.auth_provider.0', 'google');
    }

    public function test_google_user_cannot_request_password_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'google_id' => 'google-user-123',
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'auth_provider'])
            ->assertJsonPath('errors.email.0', 'This account uses Google sign-in. Please continue with Google.')
            ->assertJsonPath('errors.auth_provider.0', 'google');

        Notification::assertNothingSent();
    }

    public function test_google_user_cannot_register_with_password_using_the_same_email(): void
    {
        $user = User::factory()->create([
            'email' => 'google@example.com',
            'google_id' => 'google-user-123',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Google User',
            'email' => $user->email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'auth_provider'])
            ->assertJsonPath('errors.email.0', 'This email already uses Google sign-in. Please continue with Google.')
            ->assertJsonPath('errors.auth_provider.0', 'google');
    }

    public function test_unverified_user_cannot_login(): void
    {
        $user = User::factory()->unverified()->create([
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_unverified_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->postJson('/api/auth/email/verification-notification', [
            'email' => $user->email,
        ]);

        $response->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_user_can_verify_email_from_signed_link(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ],
            false,
        );

        $response = $this->getJson($this->toRelativeUrl($verificationUrl));

        $response->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mailMessage = $notification->toMail($user);
            $actionUrl = $mailMessage->actionUrl;
            parse_str(parse_url($actionUrl, PHP_URL_QUERY) ?: '', $query);

            $this->assertSame('Reset your Neuroflow password', $mailMessage->subject);
            $this->assertSame("Hi {$user->name},", $mailMessage->greeting);
            $this->assertSame('Reset password', $mailMessage->actionText);

            return str_starts_with(
                $actionUrl,
                Config::get('app.frontend_url').'/auth/reset-password/'.rawurlencode($notification->token),
            ) && ($query['email'] ?? null) === $user->email;
        });
    }

    public function test_user_can_reset_password_and_existing_tokens_are_revoked(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPassword123',
        ]);

        $user->createToken('existing_token');
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('current_session')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function toRelativeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $query = parse_url($url, PHP_URL_QUERY);

        if (! $query) {
            return $path;
        }

        return $path.'?'.$query;
    }
}
