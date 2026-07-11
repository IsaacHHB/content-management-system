<?php

use App\Models\ContactSubmission;

test('contact submissions are stored with a one-way ip hash', function () {
    $this->postJson(route('contact.store'), [
        'name' => 'Community Member',
        'email' => 'member@example.com',
        'subject' => 'Program question',
        'message' => 'Please send more information.',
        'form_started_at' => now()->subSeconds(5)->timestamp,
    ])->assertRedirect();

    $submission = ContactSubmission::firstOrFail();
    expect($submission->ip_hash)->toHaveLength(64)
        ->and($submission->ip_hash)->not->toBe('127.0.0.1');
});

test('contact honeypot and minimum completion time reject bots', function () {
    $this->postJson(route('contact.store'), [
        'name' => 'Bot', 'email' => 'bot@example.com', 'subject' => 'Spam', 'message' => 'Spam',
        'website' => 'https://spam.example', 'form_started_at' => now()->timestamp,
    ])->assertUnprocessable();

    expect(ContactSubmission::count())->toBe(0);
});
