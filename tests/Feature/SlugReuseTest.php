<?php

use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('a soft-deleted program releases its slug for reuse', function () {
    $user = User::factory()->create();

    $original = Program::create([
        'title' => 'Fatherhood', 'excerpt' => 'x', 'blocks' => [],
        'status' => PublishStatus::Draft, 'created_by' => $user->id, 'updated_by' => $user->id,
    ]);
    expect($original->slug)->toBe('fatherhood');
    $original->delete();

    $replacement = Program::create([
        'title' => 'Fatherhood', 'excerpt' => 'x', 'blocks' => [],
        'status' => PublishStatus::Draft, 'created_by' => $user->id, 'updated_by' => $user->id,
    ]);

    expect((int) $original->fresh()->slug_lock)->toBe($original->id)
        ->and((int) $replacement->slug_lock)->toBe(0)
        ->and($replacement->slug)->toBe('fatherhood');
});

test('two live rows are still kept distinct by an appended suffix', function () {
    $user = User::factory()->create();

    $first = Program::create([
        'title' => 'Shared', 'excerpt' => 'x', 'blocks' => [],
        'status' => PublishStatus::Draft, 'created_by' => $user->id, 'updated_by' => $user->id,
    ]);
    $second = Program::create([
        'title' => 'Shared', 'excerpt' => 'x', 'blocks' => [],
        'status' => PublishStatus::Draft, 'created_by' => $user->id, 'updated_by' => $user->id,
    ]);

    expect($first->slug)->toBe('shared')->and($second->slug)->toBe('shared-1');
});

test('a soft-deleted root page releases its slug for reuse', function () {
    $user = User::factory()->create();

    $original = Page::create(['title' => 'About', 'slug' => 'about', 'blocks' => [], 'status' => 'draft', 'created_by' => $user->id]);
    $original->delete();

    $replacement = Page::create(['title' => 'About', 'slug' => 'about', 'blocks' => [], 'status' => 'draft', 'created_by' => $user->id]);

    expect($replacement->path)->toBe('/about')
        ->and((int) $original->fresh()->slug_lock)->toBe($original->id);
});

test('restoring is blocked when the slug was reclaimed by a live row', function () {
    $superAdmin = User::factory()->withTwoFactor()->create();
    $superAdmin->assignRole('super_admin');

    $original = Program::create([
        'title' => 'Youth', 'slug' => 'youth', 'excerpt' => 'x', 'blocks' => [],
        'status' => PublishStatus::Draft, 'created_by' => $superAdmin->id, 'updated_by' => $superAdmin->id,
    ]);
    $original->delete();

    Program::create([
        'title' => 'Youth Services', 'slug' => 'youth', 'excerpt' => 'x', 'blocks' => [],
        'status' => PublishStatus::Draft, 'created_by' => $superAdmin->id, 'updated_by' => $superAdmin->id,
    ]);

    $this->actingAs($superAdmin)->postJson(route('admin.programs.restore', $original->id))
        ->assertUnprocessable();

    expect($original->fresh()->trashed())->toBeTrue();
});

test('restoring succeeds when the slug is still free', function () {
    $superAdmin = User::factory()->withTwoFactor()->create();
    $superAdmin->assignRole('super_admin');

    $original = Program::create([
        'title' => 'Elders', 'slug' => 'elders', 'excerpt' => 'x', 'blocks' => [],
        'status' => PublishStatus::Draft, 'created_by' => $superAdmin->id, 'updated_by' => $superAdmin->id,
    ]);
    $original->delete();

    $this->actingAs($superAdmin)->postJson(route('admin.programs.restore', $original->id))
        ->assertOk();

    $restored = $original->fresh();
    expect($restored->trashed())->toBeFalse()
        ->and((int) $restored->slug_lock)->toBe(0)
        ->and($restored->slug)->toBe('elders');
});
