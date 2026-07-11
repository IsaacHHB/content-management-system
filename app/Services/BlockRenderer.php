<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlockRenderer
{
    /** @var array<string, list<string>> */
    private const FIELDS = [
        'hero' => ['heading', 'sub', 'media_asset_id', 'cta'],
        'rich_text' => ['content'],
        'image' => ['media_asset_id', 'caption', 'alt', 'width'],
        'image_text' => ['media_asset_id', 'content', 'alt', 'image_position'],
        'gallery_embed' => ['gallery_id'],
        'video_embed' => ['url', 'title'],
        'cards' => ['columns', 'cards'],
        'cta_banner' => ['heading', 'text', 'button'],
        'events_list' => ['count', 'heading'],
        'news_list' => ['count', 'heading'],
        'team_grid' => ['heading', 'member_ids'],
        'accordion' => ['heading', 'items'],
        'divider' => ['style'],
        'spacer' => ['size'],
    ];

    /**
     * @param  array<string, mixed>|list<mixed>  $input
     * @return list<array{id: string, type: string, data: array<string, mixed>}>
     */
    public function sanitize(array $input): array
    {
        $blocks = Arr::isAssoc($input) && array_key_exists('blocks', $input) ? $input['blocks'] : $input;

        if (! is_array($blocks) || count($blocks) > 100) {
            throw ValidationException::withMessages(['blocks' => 'Blocks must be an array containing at most 100 items.']);
        }

        $sanitized = [];
        $ids = [];

        foreach ($blocks as $index => $block) {
            if (! is_array($block) || ! isset($block['type'], $block['data'])) {
                throw ValidationException::withMessages(["blocks.{$index}" => 'Every block requires a type and data.']);
            }

            $type = (string) $block['type'];
            if (! isset(self::FIELDS[$type])) {
                throw ValidationException::withMessages(["blocks.{$index}.type" => 'This block type is not supported.']);
            }

            $id = isset($block['id']) && is_string($block['id']) && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $block['id'])
                ? $block['id']
                : (string) Str::uuid();
            if (isset($ids[$id])) {
                throw ValidationException::withMessages(["blocks.{$index}.id" => 'Block IDs must be unique.']);
            }
            $ids[$id] = true;

            $data = array_intersect_key((array) $block['data'], array_flip(self::FIELDS[$type]));
            $sanitized[] = ['id' => $id, 'type' => $type, 'data' => $this->sanitizeData($type, $data, $index)];
        }

        return $sanitized;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeData(string $type, array $data, int $index): array
    {
        foreach ($data as $key => $value) {
            if (str_ends_with($key, '_id') && $value !== null) {
                if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                    throw ValidationException::withMessages(["blocks.{$index}.data.{$key}" => 'Referenced IDs must be positive integers.']);
                }
                $data[$key] = (int) $value;
            }

            if (is_string($value)) {
                $data[$key] = trim(strip_tags($value));
            }
        }

        if (in_array($type, ['rich_text', 'image_text'], true) && isset($data['content'])) {
            $data['content'] = $this->sanitizeTiptap($data['content']);
        }

        if ($type === 'video_embed' && isset($data['url']) && ! $this->isAllowedVideoUrl((string) $data['url'])) {
            throw ValidationException::withMessages(["blocks.{$index}.data.url" => 'Only YouTube and Vimeo URLs are supported.']);
        }

        if (isset($data['width']) && ! in_array($data['width'], ['normal', 'wide', 'full'], true)) {
            $data['width'] = 'normal';
        }
        if (isset($data['image_position']) && ! in_array($data['image_position'], ['left', 'right'], true)) {
            $data['image_position'] = 'left';
        }

        foreach (['cta', 'button'] as $linkKey) {
            if (isset($data[$linkKey]) && is_array($data[$linkKey])) {
                $data[$linkKey] = $this->sanitizeLink($data[$linkKey]);
            }
        }

        // Nested list fields (cards, accordion items) carry their own strings,
        // links, and rich-text bodies that the top-level pass never reaches.
        foreach (['cards', 'items'] as $listKey) {
            if (isset($data[$listKey]) && is_array($data[$listKey])) {
                $data[$listKey] = array_map(
                    fn ($entry) => $this->sanitizeDeep($entry, 1),
                    array_slice($data[$listKey], 0, 50),
                );
            }
        }

        if (isset($data['count'])) {
            $data['count'] = max(1, min(12, (int) $data['count']));
        }

        return $data;
    }

    /**
     * Recursively sanitize an arbitrary nested value from a block: strings are
     * tag-stripped, `_id` keys coerced to positive ints, `url`/`href` keys held
     * to the safe-link allowlist, and any Tiptap `content` node re-sanitized.
     */
    private function sanitizeDeep(mixed $value, int $depth): mixed
    {
        if (is_string($value)) {
            return trim(strip_tags($value));
        }

        if (! is_array($value) || $depth > 12) {
            return is_array($value) ? [] : $value;
        }

        $out = [];
        foreach (array_slice($value, 0, 100, true) as $key => $item) {
            if ($key === 'media') {
                // Preview-only hydrated object; never persisted.
                continue;
            }
            if ($key === 'content' && is_array($item)) {
                $out[$key] = $this->sanitizeTiptap($item);
            } elseif (str_ends_with((string) $key, '_id') && $item !== null) {
                $out[$key] = filter_var($item, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false ? null : (int) $item;
            } elseif (in_array($key, ['url', 'href'], true) && is_string($item)) {
                $out[$key] = $this->isSafeLink($item) ? $item : '';
            } else {
                $out[$key] = $this->sanitizeDeep($item, $depth + 1);
            }
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function sanitizeTiptap(mixed $value, int $depth = 0): array
    {
        if (! is_array($value) || $depth > 30) {
            return ['type' => 'doc', 'content' => []];
        }

        $allowedNodes = ['doc', 'paragraph', 'heading', 'bulletList', 'orderedList', 'listItem', 'text', 'blockquote', 'hardBreak', 'horizontalRule'];
        $allowedMarks = ['bold', 'italic', 'link'];
        $type = in_array($value['type'] ?? null, $allowedNodes, true) ? $value['type'] : 'paragraph';
        $node = ['type' => $type];

        if ($type === 'text') {
            $node['text'] = mb_substr(strip_tags((string) ($value['text'] ?? '')), 0, 50000);
        }
        if ($type === 'heading') {
            $node['attrs'] = ['level' => in_array($value['attrs']['level'] ?? null, [2, 3], true) ? $value['attrs']['level'] : 2];
        }
        if (isset($value['marks']) && is_array($value['marks'])) {
            $node['marks'] = [];
            foreach ($value['marks'] as $mark) {
                if (! is_array($mark) || ! in_array($mark['type'] ?? null, $allowedMarks, true)) {
                    continue;
                }
                $cleanMark = ['type' => $mark['type']];
                if ($mark['type'] === 'link' && $this->isSafeLink((string) ($mark['attrs']['href'] ?? ''))) {
                    $cleanMark['attrs'] = ['href' => $mark['attrs']['href']];
                } elseif ($mark['type'] === 'link') {
                    continue;
                }
                $node['marks'][] = $cleanMark;
            }
        }
        if (isset($value['content']) && is_array($value['content'])) {
            $node['content'] = array_map(fn ($child) => $this->sanitizeTiptap($child, $depth + 1), array_slice($value['content'], 0, 1000));
        }

        return $node;
    }

    /**
     * @param  array<string, mixed>  $link
     * @return array<string, string>
     */
    private function sanitizeLink(array $link): array
    {
        $url = (string) ($link['url'] ?? '');

        return [
            'label' => mb_substr(trim(strip_tags((string) ($link['label'] ?? ''))), 0, 100),
            'url' => $this->isSafeLink($url) ? $url : '',
        ];
    }

    private function isSafeLink(string $url): bool
    {
        // Relative internal path, but not a protocol-relative ("//host") or
        // backslash-smuggled ("/\host") URL that resolves off-site.
        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\');
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https', 'mailto'], true);
    }

    private function isAllowedVideoUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), [
            'youtube.com', 'www.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com',
        ], true);
    }
}
