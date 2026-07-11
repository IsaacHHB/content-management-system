<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('the last active super administrator cannot be demoted or deactivated', function () {
    $superAdmin = User::factory()->withTwoFactor()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin)->putJson(route('admin.users.update', $superAdmin), [
        'is_active' => false,
    ])->assertUnprocessable();

    expect($superAdmin->refresh()->is_active)->toBeTrue()
        ->and($superAdmin->hasRole('super_admin'))->toBeTrue();
});

test('super administrators are redirected to configure two factor authentication', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin)->get(route('admin.dashboard'))
        ->assertRedirect(route('security.edit'));
});
