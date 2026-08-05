<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\NewAccessToken;

interface IUserRepository extends IRepository
{
    public function searchByNameOrEmail(
        ?string $search,
        ?string $name,
        ?string $email,
        int $paginationAmount,
    ): LengthAwarePaginator;

    public function findByEmail(string $email): ?User;

    public function findByGoogleId(string $googleId): ?User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateGoogleIdentity(User $user, array $data): bool;

    public function createAccessToken(User $user, string $tokenName): NewAccessToken;

    public function deleteAccessTokens(User $user): void;

    public function deleteCurrentAccessToken(User $user): void;

    public function markEmailAsVerified(User $user): bool;

    public function updatePassword(User $user, string $password): bool;
}
