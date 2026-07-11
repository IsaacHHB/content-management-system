<?php

use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
});

function makeMember(string $name, string $group, int $order): TeamMember
{
    $user = User::first() ?? User::factory()->create();

    return TeamMember::create([
        'name' => $name, 'title' => 'Role', 'group' => $group, 'bio' => 'Biography paragraph.',
        'show_email' => false, 'show_phone' => false, 'is_active' => true,
        'sort_order' => $order, 'created_by' => $user->id, 'updated_by' => $user->id,
    ]);
}

test('the team index groups active members into staff and board', function () {
    makeMember('Staff One', 'staff', 0);
    makeMember('Board One', 'board', 1);
    makeMember('Inactive', 'staff', 2)->update(['is_active' => false]);

    $this->get('/about/team')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/team/index')
            ->where('groups.0.key', 'staff')
            ->has('groups.0.members', 1)
            ->where('groups.1.key', 'board')
            ->has('groups.1.members', 1));
});

test('a member page exposes the member and prev/next siblings for cycling', function () {
    makeMember('Alpha', 'staff', 0);
    makeMember('Bravo', 'staff', 1);
    makeMember('Charlie', 'staff', 2);

    $this->get('/about/team/bravo')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/team/show')
            ->where('member.name', 'Bravo')
            ->where('siblings.prev.slug', 'alpha')
            ->where('siblings.next.slug', 'charlie'));
});

test('an unknown team member slug 404s', function () {
    $this->get('/about/team/nobody')->assertNotFound();
});

test('saving a team member generates an OG share image', function () {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('imagick not available');
    }

    $member = makeMember('Og Person', 'staff', 0);

    expect($member->fresh()->og_image_path)->toBe('og/team/og-person.jpg');
    Storage::disk('public')->assertExists('og/team/og-person.jpg');
});
