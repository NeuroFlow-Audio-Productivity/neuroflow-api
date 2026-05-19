<?php

namespace App\Services\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface IUserService extends IService
{
    public function getAllUsers(): Collection;

    public function paginateUsers(int $paginationAmount = 15): LengthAwarePaginator;

    public function searchByNameOrEmail(
        ?string $search,
        ?string $name,
        ?string $email,
        int $paginationAmount = 15,
    ): LengthAwarePaginator;

    public function createUser(array $data): User;

    public function updateUser(User $user, array $data): User;

    public function deleteUser(User $user): bool;

    public function register(array $data): User;

    /**
     * @return array{access_token: string, token_type: string, user: User}
     */
    public function login(array $credentials): array;

    /**
     * @param  array{redirect_uri?: string, state?: string}  $parameters
     */
    public function googleAuthorizationUrl(array $parameters = []): string;

    /**
     * @return array{access_token: string, token_type: string, user: User}
     */
    public function loginWithGoogle(array $data): array;

    public function resendEmailVerification(string $email): void;

    public function verifyEmail(int $userId, string $hash): User;

    public function sendPasswordResetLink(string $email): void;

    public function resetPassword(array $data): void;

    public function logout(User $user): void;
}
