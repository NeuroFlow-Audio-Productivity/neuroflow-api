<?php

namespace App\Policies;

use App\Models\FlowNode;
use App\Models\User;

class FlowNodePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FlowNode $flowNode): bool
    {
        return $this->ownsFlow($user, $flowNode);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FlowNode $flowNode): bool
    {
        return $this->ownsFlow($user, $flowNode);
    }

    public function delete(User $user, FlowNode $flowNode): bool
    {
        return $this->ownsFlow($user, $flowNode);
    }

    private function ownsFlow(User $user, FlowNode $flowNode): bool
    {
        return $flowNode->flow()
            ->where('user_id', $user->id)
            ->exists();
    }
}
