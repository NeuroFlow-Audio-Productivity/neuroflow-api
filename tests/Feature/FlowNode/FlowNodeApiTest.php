<?php

namespace Tests\Feature\FlowNode;

use App\Models\Audio;
use App\Models\Flow;
use App\Models\FlowNode;
use App\Models\Mode;
use App\Models\User;
use Database\Seeders\ProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FlowNodeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProfileSeeder::class);
    }

    public function test_flow_nodes_require_authentication(): void
    {
        $this->getJson('/api/flow-nodes')->assertUnauthorized();
        $this->postJson('/api/flow-nodes')->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_only_nodes_from_selected_owned_flow(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $flow = Flow::factory()->create(['user_id' => $user->id]);
        $otherOwnedFlow = Flow::factory()->create(['user_id' => $user->id]);
        $otherFlow = Flow::factory()->create(['user_id' => $otherUser->id]);
        $mode = Mode::factory()->create(['name' => 'Focus']);
        $audio = Audio::factory()->create(['name' => 'Bell']);

        Sanctum::actingAs($user);

        FlowNode::factory()->create([
            'time' => 25,
            'order' => 1,
            'flow_id' => $flow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ]);
        FlowNode::factory()->create([
            'time' => 5,
            'order' => 2,
            'flow_id' => $flow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ]);
        FlowNode::factory()->create([
            'time' => 50,
            'order' => 1,
            'flow_id' => $otherOwnedFlow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ]);
        FlowNode::factory()->create([
            'time' => 60,
            'order' => 1,
            'flow_id' => $otherFlow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ]);

        $response = $this->getJson("/api/flow-nodes?flow_id={$flow->id}");

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['time' => 25, 'order' => 1, 'flow_id' => $flow->id])
            ->assertJsonFragment(['time' => 5, 'order' => 2, 'flow_id' => $flow->id])
            ->assertJsonMissing(['time' => 50, 'flow_id' => $otherOwnedFlow->id])
            ->assertJsonMissing(['time' => 60, 'flow_id' => $otherFlow->id]);
    }

    public function test_user_cannot_list_nodes_without_an_owned_flow(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherFlow = Flow::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/flow-nodes')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['flow_id']);

        $this->getJson('/api/flow-nodes?flow_id=999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['flow_id']);

        $this->getJson("/api/flow-nodes?flow_id={$otherFlow->id}")
            ->assertForbidden();
    }

    public function test_authenticated_user_can_paginate_their_flow_nodes(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        FlowNode::factory()->count(3)->create([
            'flow_id' => $flow->id,
        ]);

        $response = $this->getJson("/api/flow-nodes?flow_id={$flow->id}&pagination_amount=2");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $this->getJson("/api/flow-nodes?flow_id={$flow->id}&pagination_amount=2&page=2")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_owner_can_create_a_flow_node(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->create(['user_id' => $user->id]);
        $mode = Mode::factory()->create();
        $audio = Audio::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/flow-nodes', [
            'time' => 25,
            'order' => 1,
            'flow_id' => $flow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('time', 25)
            ->assertJsonPath('order', 1)
            ->assertJsonPath('flow_id', $flow->id)
            ->assertJsonPath('mode_id', $mode->id)
            ->assertJsonPath('end_audio_id', $audio->id);

        $this->assertDatabaseHas('flow_nodes', [
            'time' => 25,
            'order' => 1,
            'flow_id' => $flow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ]);
    }

    public function test_owner_can_view_update_and_delete_a_flow_node(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->create(['user_id' => $user->id]);
        $otherOwnedFlow = Flow::factory()->create(['user_id' => $user->id]);
        $mode = Mode::factory()->create();
        $audio = Audio::factory()->create();
        $flowNode = FlowNode::factory()->create([
            'time' => 25,
            'order' => 1,
            'flow_id' => $flow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/flow-nodes/{$flowNode->id}")
            ->assertOk()
            ->assertJsonPath('id', $flowNode->id)
            ->assertJsonPath('time', 25)
            ->assertJsonPath('order', 1)
            ->assertJsonPath('flow.id', $flow->id)
            ->assertJsonPath('mode.id', $mode->id)
            ->assertJsonPath('end_audio.id', $audio->id);

        $this->patchJson("/api/flow-nodes/{$flowNode->id}", [
            'time' => 10,
            'order' => 2,
            'flow_id' => $otherOwnedFlow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ])
            ->assertOk()
            ->assertJsonPath('time', 10)
            ->assertJsonPath('order', 2)
            ->assertJsonPath('flow_id', $otherOwnedFlow->id);

        $this->assertDatabaseHas('flow_nodes', [
            'id' => $flowNode->id,
            'time' => 10,
            'order' => 2,
            'flow_id' => $otherOwnedFlow->id,
        ]);

        $this->deleteJson("/api/flow-nodes/{$flowNode->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('flow_nodes', ['id' => $flowNode->id]);
    }

    public function test_non_owner_cannot_create_view_update_or_delete_flow_nodes(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $flow = Flow::factory()->create(['user_id' => $owner->id]);
        $mode = Mode::factory()->create();
        $audio = Audio::factory()->create();
        $flowNode = FlowNode::factory()->create([
            'time' => 25,
            'order' => 1,
            'flow_id' => $flow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ]);

        Sanctum::actingAs($otherUser);

        $this->postJson('/api/flow-nodes', [
            'time' => 10,
            'order' => 2,
            'flow_id' => $flow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ])->assertForbidden();

        $this->getJson("/api/flow-nodes/{$flowNode->id}")
            ->assertForbidden();

        $this->patchJson("/api/flow-nodes/{$flowNode->id}", [
            'time' => 10,
            'order' => 2,
            'flow_id' => $flow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ])->assertForbidden();

        $this->deleteJson("/api/flow-nodes/{$flowNode->id}")
            ->assertForbidden();

        $this->assertDatabaseMissing('flow_nodes', [
            'time' => 10,
            'order' => 2,
            'flow_id' => $flow->id,
        ]);
        $this->assertDatabaseHas('flow_nodes', [
            'id' => $flowNode->id,
            'time' => 25,
            'order' => 1,
            'flow_id' => $flow->id,
        ]);
    }

    public function test_owner_cannot_move_node_to_another_users_flow(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $flow = Flow::factory()->create(['user_id' => $user->id]);
        $otherFlow = Flow::factory()->create(['user_id' => $otherUser->id]);
        $mode = Mode::factory()->create();
        $audio = Audio::factory()->create();
        $flowNode = FlowNode::factory()->create([
            'flow_id' => $flow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/flow-nodes/{$flowNode->id}", [
            'time' => 10,
            'order' => 2,
            'flow_id' => $otherFlow->id,
            'mode_id' => $mode->id,
            'end_audio_id' => $audio->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('flow_nodes', [
            'id' => $flowNode->id,
            'flow_id' => $flow->id,
        ]);
    }

    public function test_flow_node_fields_are_required_and_must_exist(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->create(['user_id' => $user->id]);
        $flowNode = FlowNode::factory()->create(['flow_id' => $flow->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/flow-nodes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['time', 'order', 'flow_id', 'mode_id', 'end_audio_id']);

        $this->postJson('/api/flow-nodes', [
            'time' => 0,
            'order' => 0,
            'flow_id' => 999,
            'mode_id' => 999,
            'end_audio_id' => 999,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['time', 'order', 'flow_id', 'mode_id', 'end_audio_id']);

        $this->patchJson("/api/flow-nodes/{$flowNode->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['time', 'order', 'flow_id', 'mode_id', 'end_audio_id']);
    }
}
