<?php

namespace Tests\Feature\Mode;

use App\Models\Mode;
use App\Models\User;
use Database\Seeders\ModeSeeder;
use Database\Seeders\ProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProfileSeeder::class);
    }

    public function test_modes_require_authentication(): void
    {
        $this->getJson('/api/modes')->assertUnauthorized();
        $this->postJson('/api/modes')->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_modes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Mode::factory()->create([
            'name' => 'Focus',
            'description' => 'Focused audio and flow behavior.',
            'color' => '#3366FF',
            'is_system' => false,
        ]);
        Mode::factory()->create([
            'name' => 'Calm',
            'description' => 'Gentler audio and flow behavior.',
            'color' => '#22AA88',
            'is_system' => false,
        ]);

        $response = $this->getJson('/api/modes');

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['name' => 'Focus', 'color' => '#3366FF'])
            ->assertJsonFragment(['name' => 'Calm', 'color' => '#22AA88']);
    }

    public function test_authenticated_user_can_paginate_modes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Mode::factory()->count(3)->create();

        $response = $this->getJson('/api/modes?pagination_amount=2');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/modes?pagination_amount=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_authenticated_user_can_get_all_modes_without_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Mode::factory()->count(3)->create();

        $this->getJson('/api/modes/all?pagination_amount=1')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_mode_seeder_creates_default_modes_idempotently(): void
    {
        $this->seed(ModeSeeder::class);
        $this->seed(ModeSeeder::class);

        $this->assertDatabaseCount('modes', 4);
        $this->assertDatabaseHas('modes', [
            'name' => 'Sleep',
            'description' => 'Beta waves: discreet pulses for distraction-free work blocks.',
            'color' => '#6ee7d8',
            'is_system' => false,
        ]);
        $this->assertDatabaseHas('modes', [
            'name' => 'Relax',
            'description' => 'Theta waves: textured ambience to slow down mental noise.',
            'color' => '#f6c177',
            'is_system' => false,
        ]);
        $this->assertDatabaseHas('modes', [
            'name' => 'Sleep',
            'description' => 'Delta waves: automatic fade-out for falling asleep.',
            'color' => '#b9a7ff',
            'is_system' => false,
        ]);
        $this->assertDatabaseHas('modes', [
            'name' => 'Session Alarm',
            'description' => 'Controls end-of-session alarm sounds for Pomodoro and timer completion events.',
            'color' => '#F97316',
            'is_system' => true,
        ]);
    }

    public function test_mode_seeder_updates_existing_default_descriptions(): void
    {
        Mode::query()->create([
            'name' => 'Sleep',
            'description' => 'Discreet beta pulses for distraction-free work blocks.',
            'color' => '#6ee7d8',
            'is_system' => false,
        ]);
        Mode::query()->create([
            'name' => 'Relax',
            'description' => 'Theta textures to slow down mental noise.',
            'color' => '#f6c177',
            'is_system' => false,
        ]);
        Mode::query()->create([
            'name' => 'Sleep',
            'description' => 'Delta waves with automatic fade-out for falling asleep.',
            'color' => '#b9a7ff',
            'is_system' => false,
        ]);

        $this->seed(ModeSeeder::class);

        $this->assertDatabaseCount('modes', 4);
        $this->assertDatabaseHas('modes', [
            'name' => 'Sleep',
            'description' => 'Beta waves: discreet pulses for distraction-free work blocks.',
            'color' => '#6ee7d8',
            'is_system' => false,
        ]);
        $this->assertDatabaseHas('modes', [
            'name' => 'Relax',
            'description' => 'Theta waves: textured ambience to slow down mental noise.',
            'color' => '#f6c177',
            'is_system' => false,
        ]);
        $this->assertDatabaseHas('modes', [
            'name' => 'Sleep',
            'description' => 'Delta waves: automatic fade-out for falling asleep.',
            'color' => '#b9a7ff',
            'is_system' => false,
        ]);
        $this->assertDatabaseHas('modes', [
            'name' => 'Session Alarm',
            'description' => 'Controls end-of-session alarm sounds for Pomodoro and timer completion events.',
            'color' => '#F97316',
            'is_system' => true,
        ]);
    }

    public function test_authenticated_user_can_view_a_mode(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $mode = Mode::factory()->create([
            'name' => 'Deep Work',
            'description' => 'Mode for uninterrupted flow sessions.',
            'color' => '#111AAA',
        ]);

        $response = $this->getJson("/api/modes/{$mode->id}");

        $response
            ->assertOk()
            ->assertJsonPath('id', $mode->id)
            ->assertJsonPath('name', 'Deep Work')
            ->assertJsonPath('description', 'Mode for uninterrupted flow sessions.')
            ->assertJsonPath('color', '#111AAA')
            ->assertJsonPath('is_system', false);
    }

    public function test_regular_user_cannot_create_update_or_delete_modes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $mode = Mode::factory()->create([
            'name' => 'Original',
            'description' => 'Original description.',
            'color' => '#123456',
        ]);

        $this->postJson('/api/modes', [
            'name' => 'Blocked',
            'description' => 'Should not be created.',
            'color' => '#654321',
            'is_system' => false,
        ])->assertForbidden();

        $this->patchJson("/api/modes/{$mode->id}", [
            'name' => 'Blocked Update',
            'description' => 'Should not be updated.',
            'color' => '#ABCDEF',
            'is_system' => true,
        ])->assertForbidden();

        $this->deleteJson("/api/modes/{$mode->id}")
            ->assertForbidden();

        $this->assertDatabaseMissing('modes', ['name' => 'Blocked']);
        $this->assertDatabaseHas('modes', [
            'id' => $mode->id,
            'name' => 'Original',
            'description' => 'Original description.',
            'color' => '#123456',
        ]);
    }

    public function test_admin_can_create_a_mode(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->postJson('/api/modes', [
            'name' => 'Focus',
            'description' => 'Focused audio and flow behavior.',
            'color' => '#3366FF',
            'is_system' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Focus')
            ->assertJsonPath('description', 'Focused audio and flow behavior.')
            ->assertJsonPath('color', '#3366FF')
            ->assertJsonPath('is_system', false);

        $this->assertDatabaseHas('modes', [
            'name' => 'Focus',
            'description' => 'Focused audio and flow behavior.',
            'color' => '#3366FF',
            'is_system' => false,
        ]);
    }

    public function test_admin_can_update_a_mode(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $mode = Mode::factory()->create([
            'name' => 'Original',
            'description' => 'Original description.',
            'color' => '#123456',
        ]);

        $response = $this->patchJson("/api/modes/{$mode->id}", [
            'name' => 'Updated',
            'description' => 'Updated description.',
            'color' => '#ABCDEF',
            'is_system' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('name', 'Updated')
            ->assertJsonPath('description', 'Updated description.')
            ->assertJsonPath('color', '#ABCDEF')
            ->assertJsonPath('is_system', true);

        $this->assertDatabaseHas('modes', [
            'id' => $mode->id,
            'name' => 'Updated',
            'description' => 'Updated description.',
            'color' => '#ABCDEF',
            'is_system' => true,
        ]);
    }

    public function test_admin_can_delete_a_mode(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $mode = Mode::factory()->create();

        $this->deleteJson("/api/modes/{$mode->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('modes', ['id' => $mode->id]);
    }

    public function test_mode_fields_are_required_and_color_must_be_hex(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/modes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'color']);

        $this->postJson('/api/modes', [
            'name' => 'Focus',
            'description' => 'Focused audio and flow behavior.',
            'color' => '3366FF',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);
    }
}
