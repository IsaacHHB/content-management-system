<?php

use App\Services\BlockRenderer;
use Illuminate\Validation\ValidationException;

test('block sanitizer strips unknown fields and unsafe rich text content', function () {
    $blocks = app(BlockRenderer::class)->sanitize([
        ['id' => 'hero-1', 'type' => 'hero', 'data' => [
            'heading' => '<script>alert(1)</script>Welcome',
            'unknown' => 'discard me',
            'cta' => ['label' => '<b>Learn</b>', 'url' => 'javascript:alert(1)'],
        ]],
        ['id' => 'copy-1', 'type' => 'rich_text', 'data' => ['content' => [
            'type' => 'doc',
            'content' => [['type' => 'text', 'text' => '<img src=x onerror=alert(1)>Safe']],
        ]]],
    ]);

    expect($blocks[0]['data'])->not->toHaveKey('unknown')
        ->and($blocks[0]['data']['heading'])->toBe('alert(1)Welcome')
        ->and($blocks[0]['data']['cta']['url'])->toBe('')
        ->and($blocks[1]['data']['content']['content'][0]['text'])->toBe('Safe');
});

test('block sanitizer rejects arbitrary iframe providers and duplicate block ids', function () {
    expect(fn () => app(BlockRenderer::class)->sanitize([
        ['id' => 'same', 'type' => 'divider', 'data' => []],
        ['id' => 'same', 'type' => 'video_embed', 'data' => ['url' => 'https://evil.example/video']],
    ]))->toThrow(ValidationException::class);
});
