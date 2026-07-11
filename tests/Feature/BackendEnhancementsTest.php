<?php

use App\Models\Event;
use App\Models\MediaAsset;
use App\Models\MediaReference;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('configured local admin is seeded with the admin role', function () {
    $this->seed(AdminSeeder::class);

    $user = User::where('email', 'isaachollowhorn@gmail.com')->firstOrFail();
    expect($user->name)->toBe('Isaac Hollow Horn Bear')
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasAllowedDomain())->toBeTrue()
        ->and(Hash::check('Password123', $user->password))->toBeTrue();
});

test('only super administrators can restore and permanently delete content', function () {
    $superAdmin = User::factory()->withTwoFactor()->create();
    $superAdmin->assignRole('super_admin');
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $page = Page::create(['title' => 'Recoverable', 'slug' => 'recoverable', 'blocks' => [], 'status' => 'draft', 'created_by' => $superAdmin->id]);
    $page->delete();

    $this->actingAs($admin)->postJson(route('admin.pages.restore', $page->id))->assertForbidden();
    $this->actingAs($superAdmin)->postJson(route('admin.pages.restore', $page->id))->assertOk();

    $page->refresh()->delete();
    $this->actingAs($superAdmin)->deleteJson(route('admin.pages.force-delete', $page->id))->assertNoContent();
    expect(Page::withTrashed()->find($page->id))->toBeNull();
});

test('all day events use date-only fields and remain queryable as upcoming', function () {
    $event = Event::create([
        'title' => 'Community Day', 'description' => [], 'status' => 'published',
        'all_day' => true, 'start_date' => today()->addDay(), 'end_date' => today()->addDays(2),
        'timezone' => 'America/Los_Angeles', 'is_virtual' => false,
    ]);

    expect($event->starts_at)->toBeNull()
        ->and(Event::upcoming()->pluck('id')->all())->toContain($event->id);
});

test('activity entries include a privacy preserving request ip hash', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)->postJson(route('admin.pages.store'), [
        'parent_id' => null, 'title' => 'Logged Page', 'slug' => 'logged-page',
        'blocks' => [], 'status' => 'draft', 'locale' => 'en',
    ])->assertCreated();

    $activity = Activity::latest()->firstOrFail();
    expect($activity->properties->get('ip_hash'))->toBeString()->toHaveLength(64)
        ->and($activity->properties->get('ip_hash'))->not->toBe('127.0.0.1');
});

test('media ids stored in settings are added to the usage index', function () {
    $this->seed(SettingSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $asset = MediaAsset::create([
        'uuid' => (string) Str::uuid(), 'type' => 'image', 'original_name' => 'logo.png',
        'created_by' => $admin->id, 'updated_by' => $admin->id,
    ]);

    $this->actingAs($admin)->putJson(route('admin.settings.update'), [
        'settings' => ['logo' => ['media_asset_id' => $asset->id]],
    ])->assertOk();

    expect(MediaReference::where('media_asset_id', $asset->id)->where('referencer_type', Setting::class)->exists())->toBeTrue()
        ->and($asset->isInUse())->toBeTrue();
});
