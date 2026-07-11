<?php

use App\Models\Partner;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('an admin can create a partner', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->postJson(route('admin.partners.store'), [
        'name' => 'Example Foundation',
        'website_url' => 'https://example.org',
        'is_active' => true,
    ])->assertRedirect();

    $partner = Partner::latest('id')->first();
    expect($partner)->not->toBeNull()
        ->and($partner->slug)->toBe('example-foundation')
        ->and($partner->website_url)->toBe('https://example.org');
});

test('a partner rejects an invalid website url', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->postJson(route('admin.partners.store'), [
        'name' => 'Bad URL Partner',
        'website_url' => 'not-a-url',
        'is_active' => true,
    ])->assertUnprocessable();
});

test('an editor cannot delete another users partner', function () {
    $owner = User::factory()->create();
    $owner->assignRole('admin');
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $partner = Partner::create([
        'name' => 'Owned Partner', 'slug' => 'owned-partner', 'is_active' => true,
        'created_by' => $owner->id, 'updated_by' => $owner->id,
    ]);

    $this->actingAs($editor)->deleteJson(route('admin.partners.destroy', $partner->id))
        ->assertForbidden();

    expect(Partner::find($partner->id))->not->toBeNull();
});

test('active partners appear in the shared public props and inactive ones do not', function () {
    Cache::forget('public_partners');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Partner::create(['name' => 'Shown Partner', 'slug' => 'shown', 'is_active' => true, 'sort_order' => 0, 'created_by' => $admin->id, 'updated_by' => $admin->id]);
    Partner::create(['name' => 'Hidden Partner', 'slug' => 'hidden', 'is_active' => false, 'sort_order' => 1, 'created_by' => $admin->id, 'updated_by' => $admin->id]);

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->has('publicPartners', 1)
            ->where('publicPartners.0.name', 'Shown Partner'));
});
