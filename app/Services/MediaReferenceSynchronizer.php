<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\MediaReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class MediaReferenceSynchronizer
{
    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $structured
     */
    public function sync(Model $referencer, array $blocks, array $structured = []): void
    {
        MediaReference::whereMorphedTo('referencer', $referencer)->delete();
        $references = [];

        foreach ($blocks as $block) {
            $this->collect($block['data'] ?? [], $references, $block['id'] ?? null);
        }
        $this->collect($structured, $references, null);

        $ids = array_values(array_unique(array_column($references, 'media_asset_id')));
        if ($ids !== [] && MediaAsset::query()->whereKey($ids)->where('type', 'image')->count() !== count($ids)) {
            throw ValidationException::withMessages(['blocks' => 'One or more referenced media assets are unavailable or are not images.']);
        }

        foreach ($references as $reference) {
            $referencer->morphMany(MediaReference::class, 'referencer')->create($reference);
        }
    }

    /**
     * @param  array<string|int, mixed>  $values
     * @param  list<array{media_asset_id: int, block_id: string|null, field: string}>  $references
     */
    private function collect(array $values, array &$references, ?string $blockId, string $path = 'data', int $depth = 0): void
    {
        if ($depth > 20) {
            return;
        }

        foreach ($values as $field => $value) {
            $fieldPath = $path.'.'.$field;
            if (str_ends_with((string) $field, 'media_asset_id') && is_numeric($value)) {
                $references[] = [
                    'media_asset_id' => (int) $value,
                    'block_id' => $blockId,
                    'field' => $fieldPath,
                ];
            } elseif (is_array($value)) {
                $this->collect($value, $references, $blockId, $fieldPath, $depth + 1);
            }
        }
    }
}
