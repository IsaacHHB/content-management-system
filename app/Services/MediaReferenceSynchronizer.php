<?php

namespace App\Services;

use App\Models\MediaReference;
use Illuminate\Database\Eloquent\Model;

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
