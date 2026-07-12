<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\Setting;
use App\Rules\ExistingImageAsset;
use App\Services\MediaReferenceSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Per-key value rules. Media keys (see config('admin.settings_media_keys'))
     * are validated separately as media-asset references. Any known key without
     * an entry here falls back to a bounded plain string.
     *
     * @var array<string, list<string>>
     */
    private const VALUE_RULES = [
        'contact_email' => ['nullable', 'email', 'max:255'],
        'contact_phone' => ['nullable', 'string', 'max:50'],
        'mailing_address' => ['nullable', 'string', 'max:500'],
        'facebook_url' => ['nullable', 'url', 'max:255'],
        'instagram_url' => ['nullable', 'url', 'max:255'],
        'youtube_url' => ['nullable', 'url', 'max:255'],
        'google_analytics_id' => ['nullable', 'string', 'max:50'],
        'site_name' => ['nullable', 'string', 'max:255'],
        'tagline' => ['nullable', 'string', 'max:255'],
        'footer_text' => ['nullable', 'string', 'max:5000'],
    ];

    public function edit(Request $request): Response
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $settings = Setting::query()->orderBy('group')->get(['key', 'value', 'group']);
        $mediaKeys = (array) config('admin.settings_media_keys', []);
        $mediaIds = $settings->whereIn('key', $mediaKeys)->pluck('value', 'key')
            ->map(fn ($v) => is_array($v) ? ($v['media_asset_id'] ?? null) : $v)
            ->filter()->values()->all();

        return Inertia::render('admin/settings/index', [
            'settings' => $settings,
            'settingsMediaKeys' => array_values($mediaKeys),
            'mediaAssets' => MediaAsset::whereIn('id', $mediaIds)->with('media')->get(),
        ]);
    }

    public function update(Request $request, MediaReferenceSynchronizer $references): RedirectResponse
    {
        abort_unless($request->user()->can('settings.manage'), 403);
        $data = $request->validate(['settings' => ['required', 'array']]);
        $known = Setting::query()->pluck('key')->all();
        abort_if(array_diff(array_keys($data['settings']), $known) !== [], 422, 'Unknown settings cannot be created through this endpoint.');

        $mediaKeys = array_values(array_map('strval', (array) config('admin.settings_media_keys', [])));
        $mediaIds = $this->validateValues($data['settings'], $mediaKeys);

        DB::transaction(function () use ($data, $references, $mediaKeys, $mediaIds): void {
            foreach ($data['settings'] as $key => $value) {
                $setting = Setting::where('key', $key)->firstOrFail();
                $setting->update(['value' => $value]);

                $structured = in_array($key, $mediaKeys, true)
                    ? ['media_asset_id' => $mediaIds[$key]]
                    : ['value' => $value];
                $references->sync($setting, [], $structured);
            }
        });

        return back()->with('success', 'Settings saved.');
    }

    /**
     * Validate each submitted value against its per-key rule and return the
     * resolved media-asset id (or null) for every media key.
     *
     * @param  array<string, mixed>  $settings
     * @param  list<string>  $mediaKeys
     * @return array<string, int|null>
     */
    private function validateValues(array $settings, array $mediaKeys): array
    {
        $mediaIds = [];

        foreach ($settings as $key => $value) {
            if (in_array($key, $mediaKeys, true)) {
                $mediaId = is_array($value) ? ($value['media_asset_id'] ?? null) : $value;
                Validator::make(['id' => $mediaId], ['id' => ['nullable', 'integer', new ExistingImageAsset]])->validate();
                $mediaIds[$key] = is_numeric($mediaId) ? (int) $mediaId : null;

                continue;
            }

            // An empty string means "cleared"; treat it as null so nullable
            // url/email rules accept it rather than rejecting a blank field.
            Validator::make(
                ['value' => $value === '' ? null : $value],
                ['value' => self::VALUE_RULES[$key] ?? ['nullable', 'string', 'max:5000']],
            )->validate();
        }

        return $mediaIds;
    }
}
