<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Imports the curated real image set from the legacy nativedadsnetwork.org
 * (committed under database/seeders/assets/) into MediaAssets on the local
 * media disk. Idempotent: an asset whose original_name already has a stored
 * file is skipped, so re-seeding will not duplicate media.
 *
 * Other seeders resolve these by original_name, e.g.
 *   MediaAsset::where('original_name', 'logo.png')->first().
 */
class LegacyMediaSeeder extends Seeder
{
    private int $userId;

    public function run(): void
    {
        $this->userId = (User::first() ?? User::factory()->create())->id;

        $base = database_path('seeders/assets');

        if (! is_dir($base)) {
            $this->command->warn("Legacy asset directory not found at {$base}; skipping media import.");

            return;
        }

        // category => human alt-text prefix
        $categories = [
            'logos' => 'Native Dads Network logo',
            'team' => 'Native Dads Network team member',
            'avatars' => 'Team member portrait',
            'partners' => 'Partner and funder logo',
            'gallery' => 'Native Dads Network community photo',
            'mural' => 'Woodland cultural mural project',
        ];

        foreach ($categories as $dir => $altPrefix) {
            $path = "{$base}/{$dir}";
            if (! is_dir($path)) {
                continue;
            }

            foreach ($this->imageFiles($path) as $file) {
                $this->importFile($file, $altPrefix);
            }
        }
    }

    /** @return array<int, string> absolute paths of importable files */
    private function imageFiles(string $path): array
    {
        $files = glob("{$path}/*.{jpg,jpeg,png,webp,gif,svg}", GLOB_BRACE) ?: [];
        sort($files);

        return $files;
    }

    private function importFile(string $absolutePath, string $altPrefix): void
    {
        $filename = basename($absolutePath);

        $existing = MediaAsset::where('original_name', $filename)->first();
        if ($existing !== null && $existing->getFirstMedia('original') !== null) {
            return; // already imported
        }

        $asset = $existing ?? MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'type' => 'image',
            'original_name' => $filename,
            'alt_text' => $this->altFor($altPrefix, $filename),
            'status' => 'active',
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);

        $asset->addMedia($absolutePath)
            ->preservingOriginal()
            ->toMediaCollection('original');
    }

    private function altFor(string $prefix, string $filename): string
    {
        $stem = Str::of(pathinfo($filename, PATHINFO_FILENAME))
            ->replace(['-', '_'], ' ')
            ->title()
            ->trim()
            ->toString();

        return trim("{$prefix} — {$stem}", ' —');
    }
}
