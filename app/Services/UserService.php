<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use App\Repositories\Interfaces\IProfileRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\Interfaces\IUserService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class UserService extends Service implements IUserService
{
    public function __construct(
        private readonly IUserRepository $userRepository,
        private readonly IProfileRepository $profileRepository,
    ) {
        parent::__construct($userRepository);
    }

    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAllWithProfile();
    }

    public function paginateUsers(int $paginationAmount = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginateWithProfile($paginationAmount);
    }

    public function searchByNameOrEmail(
        ?string $search,
        ?string $name,
        ?string $email,
        int $paginationAmount = 15,
    ): LengthAwarePaginator {
        return $this->userRepository->searchByNameOrEmail(
            $search,
            $name,
            $email,
            $paginationAmount,
        );
    }

    public function createUser(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            if (! array_key_exists('profile_id', $data)) {
                $userProfile = $this->profileRepository->findBySlug(Profile::USER_SLUG);

                if (! $userProfile) {
                    throw new RuntimeException('The default User profile has not been seeded.');
                }

                $data['profile_id'] = $userProfile->id;
            }

            return $this->userRepository->create(Arr::only($data, [
                'profile_id',
                'name',
                'email',
                'email_verified_at',
                'password',
            ]));
        });

        $user->loadMissing('profile');

        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        DB::transaction(function () use ($user, $data): void {
            $this->edit($user->id, Arr::only($data, [
                'profile_id',
                'name',
                'email',
                'email_verified_at',
                'password',
            ]));
        });

        $user->refresh();
        $user->loadMissing('profile');

        return $user;
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            $this->userRepository->deleteAccessTokens($user);

            return $this->delete($user->id);
        });
    }

    public function register(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $userProfile = $this->profileRepository->findBySlug(Profile::USER_SLUG);

            if (! $userProfile) {
                throw new RuntimeException('The default User profile has not been seeded.');
            }

            return $this->userRepository->create([
                ...Arr::only($data, ['name', 'email', 'password']),
                'profile_id' => $userProfile->id,
            ]);
        });

        $user->sendEmailVerificationNotification();
        $user->refresh();
        $user->loadMissing('profile');

        return $user;
    }

    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->google_id !== null) {
            throw ValidationException::withMessages([
                'email' => ['This account uses Google sign-in. Please continue with Google.'],
                'auth_provider' => ['google'],
            ]);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Your email address is not verified.'],
            ]);
        }

        $user->loadMissing('profile');

        $token = $this->userRepository->createAccessToken(
            $user,
            $credentials['device_name'] ?? 'auth_token'
        );

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }

    /**
     * @param  array{redirect_uri?: string, state?: string}  $parameters
     */
    public function googleAuthorizationUrl(array $parameters = []): string
    {
        $query = [
            'client_id' => $this->googleConfig('client_id'),
            'redirect_uri' => $this->googleRedirectUri($parameters['redirect_uri'] ?? null),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        if (! empty($parameters['state'])) {
            $query['state'] = $parameters['state'];
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query(
            $query,
            '',
            '&',
            PHP_QUERY_RFC3986,
        );
    }

    public function loginWithGoogle(array $data): array
    {
        $googleUser = $this->fetchGoogleUser($data);

        $user = DB::transaction(function () use ($googleUser): User {
            $user = $this->userRepository->findByGoogleId($googleUser['google_id']);

            if ($user) {
                $this->userRepository->updateGoogleIdentity($user, [
                    'google_avatar_url' => $googleUser['avatar'],
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);

                $user->refresh();

                return $user;
            }

            if ($this->userRepository->findByEmail($googleUser['email'])) {
                throw ValidationException::withMessages([
                    'email' => ['This email is registered with password login. Please sign in with email and password.'],
                    'auth_provider' => ['password'],
                ]);
            }

            $userProfile = $this->profileRepository->findBySlug(Profile::USER_SLUG);

            if (! $userProfile) {
                throw new RuntimeException('The default User profile has not been seeded.');
            }

            return $this->userRepository->create([
                'profile_id' => $userProfile->id,
                'name' => $googleUser['name'],
                'email' => $googleUser['email'],
                'email_verified_at' => now(),
                'google_id' => $googleUser['google_id'],
                'google_avatar_url' => $googleUser['avatar'],
                'password' => Str::random(64),
            ]);
        });

        $user->loadMissing('profile');

        $token = $this->userRepository->createAccessToken(
            $user,
            $data['device_name'] ?? 'google_oauth'
        );

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }

    public function resendEmailVerification(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['We could not find a user with that email address.'],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['This email address is already verified.'],
            ]);
        }

        $user->sendEmailVerificationNotification();
    }

    public function verifyEmail(int $userId, string $hash): User
    {
        $user = $this->userRepository->find($userId);

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => ['The user could not be found.'],
            ]);
        }

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw ValidationException::withMessages([
                'hash' => ['The verification link is invalid.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            $this->userRepository->markEmailAsVerified($user);
            event(new Verified($user));
        }

        $user->refresh();
        $user->loadMissing('profile');

        return $user;
    }

    public function sendPasswordResetLink(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user?->google_id !== null) {
            throw ValidationException::withMessages([
                'email' => ['This account uses Google sign-in. Please continue with Google.'],
                'auth_provider' => ['google'],
            ]);
        }

        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    public function resetPassword(array $data): void
    {
        $status = Password::reset(
            Arr::only($data, ['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $password): void {
                if ($user->google_id !== null) {
                    throw ValidationException::withMessages([
                        'email' => ['This account uses Google sign-in. Please continue with Google.'],
                        'auth_provider' => ['google'],
                    ]);
                }

                DB::transaction(function () use ($user, $password): void {
                    $this->userRepository->updatePassword($user, $password);
                    $this->userRepository->deleteAccessTokens($user);
                });

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{google_id: string, email: string, name: string, avatar: string|null}
     */
    private function fetchGoogleUser(array $data): array
    {
        $tokenPayload = [
            'client_id' => $this->googleConfig('client_id'),
            'client_secret' => $this->googleConfig('client_secret'),
            'code' => $data['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->googleRedirectUri($data['redirect_uri'] ?? null),
        ];

        if (! empty($data['code_verifier'])) {
            $tokenPayload['code_verifier'] = $data['code_verifier'];
        }

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->post('https://oauth2.googleapis.com/token', $tokenPayload);

        if ($tokenResponse->failed() || ! $tokenResponse->json('access_token')) {
            throw ValidationException::withMessages([
                'code' => ['The Google authorization code could not be exchanged.'],
            ]);
        }

        $userResponse = Http::withToken((string) $tokenResponse->json('access_token'))
            ->acceptJson()
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($userResponse->failed()) {
            throw ValidationException::withMessages([
                'code' => ['The Google user profile could not be retrieved.'],
            ]);
        }

        $payload = $userResponse->json();

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'code' => ['The Google user profile response is invalid.'],
            ]);
        }

        $googleId = trim((string) ($payload['sub'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

        if ($googleId === '' || $email === '') {
            throw ValidationException::withMessages([
                'code' => ['The Google user profile is missing the required account identifiers.'],
            ]);
        }

        if (! $emailVerified) {
            throw ValidationException::withMessages([
                'email' => ['The Google account email address is not verified.'],
            ]);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $avatar = trim((string) ($payload['picture'] ?? ''));

        return [
            'google_id' => $googleId,
            'email' => $email,
            'name' => $name !== '' ? $name : $email,
            'avatar' => $avatar !== '' ? $avatar : null,
        ];
    }

    private function googleRedirectUri(?string $redirectUri = null): string
    {
        $value = trim((string) ($redirectUri ?: config('services.google.redirect')));

        if ($value === '') {
            throw new RuntimeException('Google OAuth redirect URI is not configured.');
        }

        return $value;
    }

    private function googleConfig(string $key): string
    {
        $value = trim((string) config("services.google.{$key}"));

        if ($value === '') {
            throw new RuntimeException("Google OAuth {$key} is not configured.");
        }

        return $value;
    }

    public function logout(User $user): void
    {
        $this->userRepository->deleteCurrentAccessToken($user);
    }
}
