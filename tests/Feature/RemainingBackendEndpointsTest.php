<?php

use App\Models\ContactSubmission;
use App\Models\Event;
use App\Models\MediaAsset;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('timed and all day events validate and persist through the admin endpoint', function () {
    $timed = $this->actingAs($this->admin)->postJson(route('admin.events.store'), [
        'title' => 'Evening Gathering', 'description' => [], 'status' => 'published',
        'starts_at' => now()->addDay()->toIso8601String(), 'ends_at' => now()->addDay()->addHours(2)->toIso8601String(),
        'all_day' => false, 'timezone' => 'America/Los_Angeles', 'is_virtual' => false,
    ])->assertCreated();
    $allDay = $this->postJson(route('admin.events.store'), [
        'title' => 'Community Day', 'description' => [], 'status' => 'draft',
        'start_date' => today()->addWeek()->toDateString(), 'all_day' => true,
        'timezone' => 'America/Los_Angeles', 'is_virtual' => false,
    ])->assertCreated();

    expect(Event::findOrFail($timed->json('id'))->starts_at)->not->toBeNull()
        ->and(Event::findOrFail($allDay->json('id'))->starts_at)->toBeNull()
        ->and(Event::findOrFail($allDay->json('id'))->start_date)->not->toBeNull();
});

test('documents upload to the private local media disk and can be deleted when unused', function () {
    Storage::fake('local');
    config(['media-library.disk_name' => 'local']);

    $response = $this->actingAs($this->admin)->postJson(route('admin.media.store'), [
        'file' => UploadedFile::fake()->create('program-application.pdf', 100, 'application/pdf'),
        'caption' => 'Program application',
    ])->assertCreated();

    $asset = MediaAsset::findOrFail($response->json('id'));
    expect($asset->getFirstMedia('original'))->not->toBeNull();

    $this->deleteJson(route('admin.media.destroy', $asset))->assertNoContent();
    expect(MediaAsset::find($asset->id))->toBeNull()
        ->and(MediaAsset::withTrashed()->findOrFail($asset->id)->trashed())->toBeTrue();
});

test('categories support create update and delete', function () {
    $category = $this->actingAs($this->admin)->postJson(route('admin.categories.store'), [
        'name' => 'Events',
    ])->assertCreated()->json();

    $this->putJson(route('admin.categories.update', $category['id']), [
        'name' => 'Community Events', 'slug' => $category['slug'],
    ])->assertOk()->assertJsonPath('name', 'Community Events');
    $this->deleteJson(route('admin.categories.destroy', $category['id']))->assertNoContent();
});

test('administrators can manage the contact inbox', function () {
    $contact = ContactSubmission::create([
        'name' => 'Community Member', 'email' => 'member@example.com',
        'subject' => 'Question', 'message' => 'Hello',
    ]);

    $this->actingAs($this->admin)->patchJson(route('admin.contacts.update', $contact), [
        'is_read' => true,
    ])->assertOk()->assertJsonPath('is_read', true);
    $this->deleteJson(route('admin.contacts.destroy', $contact))->assertNoContent();
});

test('administrators can update editors but cannot modify super administrators', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($this->admin)->putJson(route('admin.users.update', $editor), [
        'name' => 'Updated Editor', 'is_active' => false,
    ])->assertOk()->assertJsonPath('name', 'Updated Editor');
    $this->putJson(route('admin.users.update', $superAdmin), ['is_active' => false])->assertForbidden();
});
