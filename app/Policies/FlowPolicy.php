<?php

namespace App\Policies;

use App\Models\Flow;
use App\Models\User;

class FlowPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Flow $flow): bool
    {
        return $user->id === $flow->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Flow $flow): bool
    {
        return $user->id === $flow->user_id;
    }

    public function delete(User $user, Flow $flow): bool
    {
        return $user->id === $flow->user_id;
    }
}
