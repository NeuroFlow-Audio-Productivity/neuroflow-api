<?php

namespace Tests\Feature\Audio;

use App\Models\Audio;
use App\Models\Mode;
use App\Models\User;
use Database\Seeders\ProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AudioApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProfileSeeder::class);
    }

    public function test_audios_require_authentication(): void
    {
        $this->getJson('/api/audios')->assertUnauthorized();
        $this->postJson('/api/audios')->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_audios(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $mode = Mode::factory()->create(['name' => 'Focus']);
        Audio::factory()->create([
            'name' => 'Focus Track',
            'path' => 'audios/focus-track.mp3',
            'mode_id' => $mode->id,
        ]);
        Audio::factory()->withoutMode()->create([
            'name' => 'Neutral Track',
            'path' => 'audios/neutral-track.mp3',
        ]);

        $response = $this->getJson('/api/audios');

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['name' => 'Focus Track', 'path' => 'audios/focus-track.mp3'])
            ->assertJsonFragment(['name' => 'Neutral Track', 'path' => 'audios/neutral-track.mp3'])
            ->assertJsonFragment(['name' => 'Focus']);

        $this->assertStringContainsString("/api/audios/{$response->json('data.0.id')}/stream?", $response->json('data.0.url'));
    }

    public function test_authenticated_user_can_paginate_audios(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Audio::factory()->count(3)->create();

        $response = $this->getJson('/api/audios?pagination_amount=2');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/audios?pagination_amount=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_authenticated_user_can_get_all_audios_without_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Audio::factory()->count(3)->create();

        $this->getJson('/api/audios/all?pagination_amount=1')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_unauthenticated_user_can_list_audios_by_mode(): void
    {
        $focusMode = Mode::factory()->create(['name' => 'Focus']);
        $calmMode = Mode::factory()->create(['name' => 'Calm']);
        Audio::factory()->create([
            'name' => 'Focus Track One',
            'path' => 'audios/focus-track-one.mp3',
            'mode_id' => $focusMode->id,
        ]);
        Audio::factory()->create([
            'name' => 'Focus Track Two',
            'path' => 'audios/focus-track-two.mp3',
            'mode_id' => $focusMode->id,
        ]);
        Audio::factory()->create([
            'name' => 'Calm Track',
            'path' => 'audios/calm-track.mp3',
            'mode_id' => $calmMode->id,
        ]);
        Audio::factory()->withoutMode()->create([
            'name' => 'Neutral Track',
            'path' => 'audios/neutral-track.mp3',
        ]);

        $response = $this->getJson("/api/modes/{$focusMode->id}/audios");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['name' => 'Focus Track One', 'mode_id' => $focusMode->id])
            ->assertJsonFragment(['name' => 'Focus Track Two', 'mode_id' => $focusMode->id])
            ->assertJsonMissing(['name' => 'Calm Track'])
            ->assertJsonMissing(['name' => 'Neutral Track']);
    }

    public function test_authenticated_user_can_view_an_audio(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $mode = Mode::factory()->create(['name' => 'Calm']);
        $audio = Audio::factory()->create([
            'name' => 'Rain Loop',
            'path' => 'audios/rain-loop.mp3',
            'mode_id' => $mode->id,
        ]);

        $response = $this->getJson("/api/audios/{$audio->id}");

        $response
            ->assertOk()
            ->assertJsonPath('id', $audio->id)
            ->assertJsonPath('name', 'Rain Loop')
            ->assertJsonPath('path', 'audios/rain-loop.mp3')
            ->assertJsonPath('mode.id', $mode->id)
            ->assertJsonPath('mode.name', 'Calm');

        $this->assertStringContainsString("/api/audios/{$audio->id}/stream?", $response->json('url'));
    }

    public function test_regular_user_cannot_create_update_or_delete_audios(): void
    {
        Storage::fake($this->audioDisk());
        Sanctum::actingAs(User::factory()->create());

        $audio = Audio::factory()->create([
            'name' => 'Original',
            'path' => 'audios/original.mp3',
        ]);

        $this->post('/api/audios', [
            'name' => 'Blocked',
            'file' => UploadedFile::fake()->create('blocked.mp3', 100, 'audio/mpeg'),
        ], ['Accept' => 'application/json'])->assertForbidden();

        $this->patch("/api/audios/{$audio->id}", [
            'name' => 'Blocked Update',
        ], ['Accept' => 'application/json'])->assertForbidden();

        $this->deleteJson("/api/audios/{$audio->id}")
            ->assertForbidden();

        $this->assertDatabaseMissing('audios', ['name' => 'Blocked']);
        $this->assertDatabaseHas('audios', [
            'id' => $audio->id,
            'name' => 'Original',
            'path' => 'audios/original.mp3',
        ]);
    }

    public function test_admin_can_create_an_audio_and_store_the_file(): void
    {
        Storage::fake($this->audioDisk());
        Sanctum::actingAs(User::factory()->admin()->create());

        $mode = Mode::factory()->create();

        $response = $this->post('/api/audios', [
            'name' => 'Morning Focus',
            'mode_id' => $mode->id,
            'file' => UploadedFile::fake()->create('morning-focus.mp3', 1024, 'audio/mpeg'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Morning Focus')
            ->assertJsonPath('mode_id', $mode->id)
            ->assertJsonPath('mode.id', $mode->id);

        $path = $response->json('path');

        $this->assertIsString($path);
        $this->assertStringStartsWith('audios/', $path);
        $this->assertStringContainsString("/api/audios/{$response->json('id')}/stream?", $response->json('url'));
        Storage::disk($this->audioDisk())->assertExists($path);

        $this->assertDatabaseHas('audios', [
            'name' => 'Morning Focus',
            'path' => $path,
            'mode_id' => $mode->id,
        ]);
    }

    public function test_signed_audio_stream_supports_byte_ranges(): void
    {
        Storage::fake($this->audioDisk());

        $audio = Audio::factory()->create([
            'path' => 'audios/range-test.mp4',
        ]);
        Storage::disk($this->audioDisk())->put($audio->path, '0123456789');

        $url = URL::temporarySignedRoute('audios.stream', now()->addMinutes(5), [
            'audio' => $audio->id,
        ]);

        $response = $this->withHeader('Range', 'bytes=2-5')->get($url);

        $response
            ->assertStatus(206)
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('Content-Range', 'bytes 2-5/10')
            ->assertHeader('Content-Length', '4');

        $this->assertSame('2345', $response->streamedContent());
    }

    public function test_admin_can_update_an_audio_and_replace_the_file(): void
    {
        Storage::fake($this->audioDisk());
        Sanctum::actingAs(User::factory()->admin()->create());

        $mode = Mode::factory()->create();
        $audio = Audio::factory()->create([
            'name' => 'Before',
            'path' => 'audios/before.mp3',
        ]);
        Storage::disk($this->audioDisk())->put($audio->path, 'old-audio');

        $response = $this->patch("/api/audios/{$audio->id}", [
            'name' => 'After',
            'mode_id' => $mode->id,
            'file' => UploadedFile::fake()->create('after.mp3', 2048, 'audio/mpeg'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertOk()
            ->assertJsonPath('name', 'After')
            ->assertJsonPath('mode_id', $mode->id);

        $newPath = $response->json('path');

        $this->assertIsString($newPath);
        $this->assertNotSame('audios/before.mp3', $newPath);
        Storage::disk($this->audioDisk())->assertMissing('audios/before.mp3');
        Storage::disk($this->audioDisk())->assertExists($newPath);

        $this->assertDatabaseHas('audios', [
            'id' => $audio->id,
            'name' => 'After',
            'path' => $newPath,
            'mode_id' => $mode->id,
        ]);
    }

    public function test_admin_can_delete_an_audio_and_remove_the_file(): void
    {
        Storage::fake($this->audioDisk());
        Sanctum::actingAs(User::factory()->admin()->create());

        $audio = Audio::factory()->create([
            'path' => 'audios/delete-me.mp3',
        ]);
        Storage::disk($this->audioDisk())->put($audio->path, 'audio');

        $this->deleteJson("/api/audios/{$audio->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('audios', ['id' => $audio->id]);
        Storage::disk($this->audioDisk())->assertMissing('audios/delete-me.mp3');
    }

    public function test_audio_fields_are_validated(): void
    {
        Storage::fake($this->audioDisk());
        Sanctum::actingAs(User::factory()->admin()->create());

        Audio::factory()->create(['name' => 'Existing Audio']);

        $this->post('/api/audios', [], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'file']);

        $this->post('/api/audios', [
            'name' => 'Existing Audio',
            'mode_id' => 999,
            'file' => UploadedFile::fake()->create('duplicate.mp3', 100, 'audio/mpeg'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'mode_id']);

        $this->post('/api/audios', [
            'name' => 'Too Large',
            'file' => UploadedFile::fake()->create('too-large.mp3', 204801, 'audio/mpeg'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    private function audioDisk(): string
    {
        return (string) config('filesystems.default');
    }
}
