<?php

use App\Models\Gallery;
use App\Models\MediaAsset;
use App\Models\MediaReference;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeMediaAsset(User $user): MediaAsset
{
    return MediaAsset::create([
        'uuid' => (string) Str::uuid(), 'type' => 'image', 'original_name' => 'photo.png',
        'created_by' => $user->id, 'updated_by' => $user->id,
    ]);
}

test('an editor can delete their own team member', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)->postJson(route('admin.team.store'), [
        'name' => 'Jane Doe', 'title' => 'Director', 'group' => 'staff', 'bio' => 'Bio',
        'show_email' => false, 'show_phone' => false, 'is_active' => true,
    ])->assertRedirect();
    $created = TeamMember::latest('id')->first();

    $this->actingAs($editor)->deleteJson(route('admin.team.destroy', $created->id))
        ->assertRedirect();

    expect(TeamMember::find($created->id))->toBeNull();
});

test('an editor cannot delete another users team member', function () {
    $owner = User::factory()->create();
    $owner->assignRole('admin');
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $member = TeamMember::create([
        'name' => 'Owned', 'slug' => 'owned', 'title' => 'Role', 'bio' => 'Bio',
        'show_email' => false, 'show_phone' => false, 'is_active' => true,
        'created_by' => $owner->id, 'updated_by' => $owner->id,
    ]);

    $this->actingAs($editor)->deleteJson(route('admin.team.destroy', $member->id))
        ->assertForbidden();

    expect(TeamMember::find($member->id))->not->toBeNull();
});

test('gallery photos are indexed in the media usage table', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $asset = makeMediaAsset($admin);

    $this->actingAs($admin)->postJson(route('admin.galleries.store'), [
        'title' => 'Community Day', 'status' => 'draft',
        'media_assets' => [
            ['id' => $asset->id, 'alt_text' => 'A family', 'sort_order' => 0],
        ],
    ])->assertRedirect();
    $gallery = Gallery::latest('id')->first();

    expect(MediaReference::where('media_asset_id', $asset->id)->where('referencer_type', Gallery::class)->where('referencer_id', $gallery->id)->exists())->toBeTrue()
        ->and($asset->fresh()->references()->count())->toBe(1);
});

test('settings reject an invalid url and a missing media asset', function () {
    $this->seed(SettingSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->putJson(route('admin.settings.update'), [
        'settings' => ['facebook_url' => 'not-a-url'],
    ])->assertUnprocessable();

    $this->actingAs($admin)->putJson(route('admin.settings.update'), [
        'settings' => ['logo' => 999999],
    ])->assertUnprocessable();

    $this->actingAs($admin)->putJson(route('admin.settings.update'), [
        'settings' => ['facebook_url' => 'https://facebook.com/ndn', 'contact_email' => ''],
    ])->assertRedirect();
});
