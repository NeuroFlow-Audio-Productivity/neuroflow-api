<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\IUserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class UserRepository extends Repository implements IUserRepository
{
    public function model(): string
    {
        return User::class;
    }

    public function getAllWithProfile(): Collection
    {
        return $this->model->newQuery()
            ->with('profile')
            ->orderBy('id')
            ->get();
    }

    public function paginateWithProfile(int $paginationAmount): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('profile')
            ->orderBy('id')
            ->paginate($paginationAmount);
    }

    public function searchByNameOrEmail(
        ?string $search,
        ?string $name,
        ?string $email,
        int $paginationAmount,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()->with('profile');

        if ($search !== null && $search !== '') {
            $query->where(function ($query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($name !== null && $name !== '') {
            $query->where('name', 'like', "%{$name}%");
        }

        if ($email !== null && $email !== '') {
            $query->where('email', 'like', "%{$email}%");
        }

        return $query
            ->orderBy('id')
            ->paginate($paginationAmount);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return $this->model->newQuery()->where('google_id', $googleId)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateGoogleIdentity(User $user, array $data): bool
    {
        return $user->forceFill($data)->save();
    }

    public function createAccessToken(User $user, string $tokenName): NewAccessToken
    {
        return $user->createToken($tokenName);
    }

    public function deleteAccessTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    public function deleteCurrentAccessToken(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function markEmailAsVerified(User $user): bool
    {
        return $user->markEmailAsVerified();
    }

    public function updatePassword(User $user, string $password): bool
    {
        return $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();
    }
}
