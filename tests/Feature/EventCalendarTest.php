<?php

use App\Enums\PublishStatus;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('the calendar buckets a published event on its date with its time', function () {
    $user = User::factory()->create();

    Event::create([
        'title' => 'Morning Circle', 'slug' => 'morning-circle', 'description' => [],
        'starts_at' => Carbon::parse('2026-08-12 06:30:00'), 'all_day' => false,
        'timezone' => 'America/Los_Angeles',
        'status' => PublishStatus::Published, 'published_at' => now(),
        'created_by' => $user->id, 'updated_by' => $user->id,
    ]);

    $this->get('/events/calendar?month=2026-08')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/events/calendar')
            ->where('month', '2026-08')
            ->where('label', 'August 2026')
            ->where('prev', '2026-07')
            ->where('next', '2026-09')
            ->has('events', 1)
            ->where('events.0.date', '2026-08-12')
            ->where('events.0.time', '6:30 AM'));
});

test('the calendar defaults to the current month and hides drafts', function () {
    $user = User::factory()->create();

    Event::create([
        'title' => 'Hidden Draft', 'slug' => 'hidden-draft', 'description' => [],
        'starts_at' => now()->startOfMonth()->addDays(3)->setTime(10, 0), 'all_day' => false,
        'timezone' => 'America/Los_Angeles',
        'status' => PublishStatus::Draft,
        'created_by' => $user->id, 'updated_by' => $user->id,
    ]);

    $this->get('/events/calendar')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('month', now()->format('Y-m'))
            ->has('events', 0));
});

test('an all-day event lands on its start_date', function () {
    $user = User::factory()->create();

    Event::create([
        'title' => 'Community Day', 'slug' => 'community-day', 'description' => [],
        'start_date' => '2026-08-20', 'all_day' => true,
        'timezone' => 'America/Los_Angeles',
        'status' => PublishStatus::Published, 'published_at' => now(),
        'created_by' => $user->id, 'updated_by' => $user->id,
    ]);

    $this->get('/events/calendar?month=2026-08')
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.date', '2026-08-20')
            ->where('events.0.time', null)
            ->where('events.0.all_day', true));
});
