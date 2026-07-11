<?php

use App\Models\User;
use Database\Seeders\LegacyContentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingSeeder::class);
    Cache::flush();
});

test('guests do not receive the admin editor chrome/preview props', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('siteChrome', null)
            ->where('blockPreviews', null));
});

test('authenticated users receive site chrome and block preview data', function () {
    $this->seed(LegacyContentSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('siteChrome.menus.header')
            ->has('siteChrome.partners')
            ->has('blockPreviews.members')
            ->has('blockPreviews.partners'));
});
