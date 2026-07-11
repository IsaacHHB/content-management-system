<?php

use App\Models\Category;
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
    $this->actingAs($this->admin)->postJson(route('admin.events.store'), [
        'title' => 'Evening Gathering', 'description' => [], 'status' => 'published',
        'starts_at' => now()->addDay()->toIso8601String(), 'ends_at' => now()->addDay()->addHours(2)->toIso8601String(),
        'all_day' => false, 'timezone' => 'America/Los_Angeles', 'is_virtual' => false,
    ])->assertRedirect();
    $timed = Event::latest('id')->first();
    $this->postJson(route('admin.events.store'), [
        'title' => 'Community Day', 'description' => [], 'status' => 'draft',
        'start_date' => today()->addWeek()->toDateString(), 'all_day' => true,
        'timezone' => 'America/Los_Angeles', 'is_virtual' => false,
    ])->assertRedirect();
    $allDay = Event::latest('id')->first();

    expect($timed->starts_at)->not->toBeNull()
        ->and($allDay->starts_at)->toBeNull()
        ->and($allDay->start_date)->not->toBeNull();
});

test('documents upload to the private local media disk and can be deleted when unused', function () {
    Storage::fake('local');
    config(['media-library.disk_name' => 'local']);

    $this->actingAs($this->admin)->postJson(route('admin.media.store'), [
        'file' => UploadedFile::fake()->create('program-application.pdf', 100, 'application/pdf'),
        'caption' => 'Program application',
    ])->assertRedirect();

    $asset = MediaAsset::latest('id')->first();
    expect($asset->getFirstMedia('original'))->not->toBeNull();

    $this->deleteJson(route('admin.media.destroy', $asset))->assertRedirect();
    expect(MediaAsset::find($asset->id))->toBeNull()
        ->and(MediaAsset::withTrashed()->findOrFail($asset->id)->trashed())->toBeTrue();
});

test('categories support create update and delete', function () {
    $this->actingAs($this->admin)->postJson(route('admin.categories.store'), [
        'name' => 'Events',
    ])->assertRedirect();
    $category = Category::latest('id')->first();

    $this->putJson(route('admin.categories.update', $category->id), [
        'name' => 'Community Events', 'slug' => $category->slug,
    ])->assertRedirect();
    expect($category->fresh()->name)->toBe('Community Events');
    $this->deleteJson(route('admin.categories.destroy', $category->id))->assertRedirect();
});

test('administrators can manage the contact inbox', function () {
    $contact = ContactSubmission::create([
        'name' => 'Community Member', 'email' => 'member@example.com',
        'subject' => 'Question', 'message' => 'Hello',
    ]);

    $this->actingAs($this->admin)->patchJson(route('admin.contacts.update', $contact), [
        'is_read' => true,
    ])->assertRedirect();
    expect($contact->fresh()->is_read)->toBeTrue();
    $this->deleteJson(route('admin.contacts.destroy', $contact))->assertRedirect();
});

test('administrators can update editors but cannot modify super administrators', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($this->admin)->putJson(route('admin.users.update', $editor), [
        'name' => 'Updated Editor', 'is_active' => false,
    ])->assertRedirect();
    expect($editor->fresh()->name)->toBe('Updated Editor');
    $this->putJson(route('admin.users.update', $superAdmin), ['is_active' => false])->assertForbidden();
});
