<?php

namespace Tests\Feature\Flow;

use App\Models\Flow;
use App\Models\User;
use Database\Seeders\ProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FlowApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProfileSeeder::class);
    }

    public function test_flows_require_authentication(): void
    {
        $this->getJson('/api/flows')->assertUnauthorized();
        $this->postJson('/api/flows')->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_only_their_flows(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        Flow::factory()->create([
            'name' => 'Morning Focus',
            'user_id' => $user->id,
        ]);
        Flow::factory()->create([
            'name' => 'Evening Reset',
            'user_id' => $user->id,
        ]);
        Flow::factory()->create([
            'name' => 'Hidden Flow',
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/flows');

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['name' => 'Morning Focus', 'user_id' => $user->id])
            ->assertJsonFragment(['name' => 'Evening Reset', 'user_id' => $user->id])
            ->assertJsonMissing(['name' => 'Hidden Flow']);
    }

    public function test_authenticated_user_can_paginate_their_flows(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Flow::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/flows?pagination_amount=2');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/flows?pagination_amount=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_authenticated_user_can_create_a_flow_as_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/flows', [
            'name' => 'Deep Work',
            'user_id' => $otherUser->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Deep Work')
            ->assertJsonPath('user_id', $user->id);

        $this->assertDatabaseHas('flows', [
            'name' => 'Deep Work',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('flows', [
            'name' => 'Deep Work',
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_owner_can_view_update_and_delete_a_flow(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $flow = Flow::factory()->create([
            'name' => 'Original Flow',
            'user_id' => $user->id,
        ]);

        $this->getJson("/api/flows/{$flow->id}")
            ->assertOk()
            ->assertJsonPath('id', $flow->id)
            ->assertJsonPath('name', 'Original Flow')
            ->assertJsonPath('user_id', $user->id);

        $this->patchJson("/api/flows/{$flow->id}", [
            'name' => 'Updated Flow',
        ])
            ->assertOk()
            ->assertJsonPath('name', 'Updated Flow')
            ->assertJsonPath('user_id', $user->id);

        $this->assertDatabaseHas('flows', [
            'id' => $flow->id,
            'name' => 'Updated Flow',
            'user_id' => $user->id,
        ]);

        $this->deleteJson("/api/flows/{$flow->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('flows', ['id' => $flow->id]);
    }

    public function test_non_owner_cannot_view_update_or_delete_a_flow(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $flow = Flow::factory()->create([
            'name' => 'Owner Flow',
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $this->getJson("/api/flows/{$flow->id}")
            ->assertForbidden();

        $this->patchJson("/api/flows/{$flow->id}", [
            'name' => 'Blocked Update',
        ])
            ->assertForbidden();

        $this->deleteJson("/api/flows/{$flow->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('flows', [
            'id' => $flow->id,
            'name' => 'Owner Flow',
            'user_id' => $owner->id,
        ]);
    }

    public function test_flow_name_is_required(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/flows', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $flow = Flow::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->patchJson("/api/flows/{$flow->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
