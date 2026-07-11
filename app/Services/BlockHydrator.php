<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Gallery;
use App\Models\MediaAsset;
use App\Models\Partner;
use App\Models\Post;
use App\Models\TeamMember;
use Illuminate\Support\Collection;

/**
 * Resolves the references a saved block stores by id (media_asset_id, gallery_id,
 * events_list, news_list, team_grid) into full objects the frontend renderer can
 * display. Used by public pages and the admin edit screen so previews match.
 */
class BlockHydrator
{
    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public function hydrate(array $blocks): array
    {
        $mediaIds = [];
        foreach ($blocks as $block) {
            $this->collectMediaIds(is_array($block['data'] ?? null) ? $block['data'] : [], $mediaIds);
        }
        $media = MediaAsset::whereIn('id', array_unique($mediaIds))->with('media')->get()->keyBy('id');

        return array_values(array_map(fn ($block) => $this->hydrateBlock($block, $media), $blocks));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $ids
     */
    private function collectMediaIds(array $data, array &$ids): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'media_asset_id' && is_numeric($value)) {
                $ids[] = (int) $value;
            } elseif (is_array($value)) {
                $this->collectMediaIds($value, $ids);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  Collection<int, MediaAsset>  $media
     * @return array<string, mixed>
     */
    private function hydrateBlock(array $block, Collection $media): array
    {
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        if (isset($data['media_asset_id']) && $media->has((int) $data['media_asset_id'])) {
            $data['media'] = $media->get((int) $data['media_asset_id']);
        }

        if (isset($data['cards']) && is_array($data['cards'])) {
            $data['cards'] = array_map(function ($card) use ($media) {
                if (isset($card['media_asset_id']) && $media->has((int) $card['media_asset_id'])) {
                    $card['media'] = $media->get((int) $card['media_asset_id']);
                }

                return $card;
            }, $data['cards']);
        }

        switch ($block['type']) {
            case 'gallery_embed':
                if (! empty($data['gallery_id'])) {
                    $data['gallery'] = Gallery::published()->with('mediaAssets.media')->find($data['gallery_id']);
                }
                break;
            case 'events_list':
                $data['events'] = Event::published()->upcoming()->orderBy('starts_at')->orderBy('start_date')->limit((int) ($data['count'] ?? 3))->get(['id', 'title', 'slug', 'starts_at', 'start_date']);
                break;
            case 'news_list':
                $data['posts'] = Post::published()->latest('published_at')->limit((int) ($data['count'] ?? 3))->get(['id', 'title', 'slug', 'excerpt', 'published_at']);
                break;
            case 'team_grid':
                $ids = $data['member_ids'] ?? [];
                $query = TeamMember::query()->where('is_active', true)->with('photo.media')->orderBy('sort_order');
                if (! empty($ids)) {
                    $query->whereIn('id', $ids);
                }
                $data['members'] = $query->get();
                break;
            case 'partners':
                $data['partners'] = Partner::query()->where('is_active', true)->with('logo.media')->orderBy('sort_order')->get();
                break;
        }

        $block['data'] = $data;

        return $block;
    }
}
