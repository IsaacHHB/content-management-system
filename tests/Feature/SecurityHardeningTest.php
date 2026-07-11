<?php

use App\Models\MediaAsset;
use App\Models\User;
use App\Services\BlockRenderer;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('an admin cannot promote a user to super administrator', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();
    $target->assignRole('editor');

    $this->actingAs($admin)->putJson(route('admin.users.update', $target), [
        'role' => 'super_admin',
    ])->assertUnprocessable();

    expect($target->refresh()->hasRole('super_admin'))->toBeFalse()
        ->and($target->hasRole('editor'))->toBeTrue();
});

test('an admin cannot self-escalate to super administrator', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->putJson(route('admin.users.update', $admin), [
        'role' => 'super_admin',
    ])->assertUnprocessable();

    expect($admin->refresh()->hasRole('super_admin'))->toBeFalse();
});

test('a super administrator can still grant the super administrator role', function () {
    $superAdmin = User::factory()->withTwoFactor()->create();
    $superAdmin->assignRole('super_admin');

    $target = User::factory()->create();
    $target->assignRole('admin');

    $this->actingAs($superAdmin)->putJson(route('admin.users.update', $target), [
        'role' => 'super_admin',
    ])->assertRedirect();

    expect($target->refresh()->hasRole('super_admin'))->toBeTrue();
});

test('a super administrator with an unconfirmed two factor secret is still forced to configure it', function () {
    $superAdmin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code'])),
        'two_factor_confirmed_at' => null,
    ]);
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin)->get(route('admin.dashboard'))
        ->assertRedirect(route('security.edit'));
});

test('nested card and accordion content is sanitized server side', function () {
    $blocks = app(BlockRenderer::class)->sanitize([
        ['id' => 'cards-1', 'type' => 'cards', 'data' => [
            'columns' => 3,
            'cards' => [
                [
                    'title' => '<script>alert(1)</script>Card',
                    'media_asset_id' => '5',
                    'link' => ['label' => 'Go', 'url' => '//evil.example/x'],
                    'content' => ['type' => 'doc', 'content' => [
                        ['type' => 'text', 'text' => '<img src=x onerror=alert(1)>Body'],
                    ]],
                ],
            ],
        ]],
        ['id' => 'faq-1', 'type' => 'accordion', 'data' => [
            'items' => [
                ['heading' => '<b>Q</b>', 'content' => ['type' => 'doc', 'content' => [
                    ['type' => 'text', 'text' => '<img src=x onerror=alert(1)>A'],
                ]]],
            ],
        ]],
    ]);

    $card = $blocks[0]['data']['cards'][0];
    expect($card['title'])->toBe('alert(1)Card')
        ->and($card['media_asset_id'])->toBe(5)
        ->and($card['content']['content'][0]['text'])->toBe('Body')
        ->and($blocks[1]['data']['items'][0]['heading'])->toBe('Q')
        ->and($blocks[1]['data']['items'][0]['content']['content'][0]['text'])->toBe('A');
});

test('protocol-relative and backslash urls are rejected as unsafe links', function () {
    $blocks = app(BlockRenderer::class)->sanitize([
        ['id' => 'cta-1', 'type' => 'cta_banner', 'data' => [
            'heading' => 'Hi',
            'button' => ['label' => 'Go', 'url' => '//evil.example/x'],
        ]],
    ]);

    expect($blocks[0]['data']['button']['url'])->toBe('');
});

test('media referenced by a scalar setting value cannot be deleted', function () {
    $this->seed(SettingSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $asset = MediaAsset::create([
        'uuid' => (string) Str::uuid(), 'type' => 'image', 'original_name' => 'logo.png',
        'created_by' => $admin->id, 'updated_by' => $admin->id,
    ]);

    // Store the media id as a bare scalar (the format the audit found unprotected).
    $this->actingAs($admin)->putJson(route('admin.settings.update'), [
        'settings' => ['logo' => $asset->id],
    ])->assertRedirect();

    expect($asset->isInUse())->toBeTrue();

    $this->actingAs($admin)->deleteJson(route('admin.media.destroy', $asset))
        ->assertUnprocessable();

    expect(MediaAsset::find($asset->id))->not->toBeNull();
});

test('deactivated accounts are rejected at login regardless of authentication path', function () {
    $user = User::factory()->create(['is_active' => false]);
    $user->assignRole('editor');

    event(new Login('web', $user, false));
})->throws(AuthenticationException::class);
