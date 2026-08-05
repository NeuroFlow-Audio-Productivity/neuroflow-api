<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Interfaces\IUserService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly IUserService $userService,
    ) {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * List all users.
     *
     * Authorization rules:
     * - Admin profile: can list every user.
     * - User profile: cannot list users. Use `GET /api/user` or `GET /api/users/{user}` for the authenticated user instead.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return UserResource::collection(
            $this->userService->listRecords(paginationAmount: $this->paginationAmount($request), with: ['profile']),
        );
    }

    /**
     * List every user without pagination.
     */
    public function getAll(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection($this->userService->getAll(with: ['profile']));
    }

    /**
     * Search users by name and/or email.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection(
            $this->userService->searchByNameOrEmail(
                $this->nullableQueryString($request, 'search'),
                $this->nullableQueryString($request, 'name'),
                $this->nullableQueryString($request, 'email'),
                $this->paginationAmount($request),
            ),
        );
    }

    /**
     * Create a user.
     *
     * Authorization rules:
     * - Admin profile: can create users and set `profile_id`, `email`, and `email_verified_at`.
     * - User profile: cannot create users.
     *
     * When `profile_id` is omitted, the user is created with the default User profile.
     */
    public function store(StoreUserRequest $request): UserResource
    {
        $user = $this->userService->createUser($request->validated());

        return new UserResource($user, Response::HTTP_CREATED);
    }

    /**
     * Show a user.
     *
     * Authorization rules:
     * - Admin profile: can view any user.
     * - User profile: can view only itself.
     *
     * The response includes the user's profile, email verification date, and timestamps.
     */
    public function show(User $user): UserResource
    {
        $user->loadMissing('profile');

        return new UserResource($user);
    }

    /**
     * Update a user.
     *
     * Authorization rules:
     * - Admin profile: can update any user and may edit `name`, `password`, `profile_id`, `email`, and `email_verified_at`.
     * - User profile: can update only itself and may edit only `name` and `password`.
     *
     * For non-admin users, any submitted `profile_id`, `email`, or `email_verified_at` fields are ignored by validation and are not changed.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user = $this->userService->updateUser($user, $request->validated());

        return new UserResource($user);
    }

    /**
     * Delete a user.
     *
     * Authorization rules:
     * - Admin profile: can delete users.
     * - User profile: cannot delete users, including itself.
     *
     * Deleting a user also revokes all of that user's access tokens.
     */
    public function destroy(User $user): HttpResponse
    {
        $this->userService->deleteUser($user);

        return response()->noContent();
    }

    private function nullableQueryString(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key, ''));

        return $value === '' ? null : $value;
    }
}
