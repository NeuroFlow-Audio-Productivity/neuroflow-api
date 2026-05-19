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
            ->assertJsonPath('user.profile.slug', Profile::USER_SLUG)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'profile' => ['id', 'name', 'slug'], 'name', 'email'],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
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
