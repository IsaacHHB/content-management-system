<?php

use App\Mail\InviteMail;
use App\Models\Invite;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admins cannot invite addresses outside the configured domains', function () {
    Mail::fake();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->postJson(route('admin.invites.store'), [
        'email' => 'outsider@yahoo.com',
        'role' => 'editor',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    expect(Invite::count())->toBe(0);
});

test('admins can issue and invitees can consume a single use hashed invite', function () {
    Mail::fake();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->postJson(route('admin.invites.store'), [
        'email' => 'new.editor@nativedadsnetwork.org',
        'role' => 'editor',
    ])->assertRedirect();

    $invite = Invite::firstOrFail();
    expect($invite->token)->toHaveLength(64)
        ->and($invite->token)->not->toContain('new.editor');

    Mail::assertQueued(InviteMail::class, function (InviteMail $mail) use ($invite) {
        $url = URL::temporarySignedRoute('invite.accept', $invite->expires_at, ['token' => $mail->plainToken]);
        $this->post(route('logout'));

        $this->post($url, [
            'name' => 'New Editor',
            'password' => 'StrongPassword12!',
            'password_confirmation' => 'StrongPassword12!',
        ])->assertRedirect(route('admin.dashboard'));

        return true;
    });

    $user = User::where('email', 'new.editor@nativedadsnetwork.org')->firstOrFail();
    expect($user->hasRole('editor'))->toBeTrue()
        ->and($invite->refresh()->accepted_at)->not->toBeNull()
        ->and($invite->pending_email)->toBeNull();
});

test('login rejects inactive users and users whose domain is no longer allowed', function () {
    $inactive = User::factory()->create(['email' => 'inactive@nativedadsnetwork.org', 'is_active' => false]);
    $this->post(route('login.store'), ['email' => $inactive->email, 'password' => 'password']);
    $this->assertGuest();

    $user = User::factory()->create(['email' => 'active@nativedadsnetwork.org']);
    config(['admin.allowed_domains' => ['another-domain.org']]);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->assertGuest();
});
