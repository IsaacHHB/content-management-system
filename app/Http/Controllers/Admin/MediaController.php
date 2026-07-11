<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request);

        return response()->json(MediaAsset::query()->withCount('references')->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('original_name', 'like', "%{$s}%")->orWhere('alt_text', 'like', "%{$s}%")))->latest()->paginate(30));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request);
        $data = $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:25600'], 'alt_text' => ['nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:2000'], 'credit' => ['nullable', 'string', 'max:255']]);
        $isImage = str_starts_with((string) $data['file']->getMimeType(), 'image/');
        abort_if($isImage && $data['file']->getSize() > 15 * 1024 * 1024, 422, 'Images may not exceed 15 MB.');
        $asset = DB::transaction(function () use ($request, $data) {
            $asset = MediaAsset::create(['uuid' => (string) Str::uuid(), 'type' => str_starts_with($data['file']->getMimeType(), 'image/') ? 'image' : 'document', 'original_name' => $data['file']->getClientOriginalName(), 'alt_text' => $data['alt_text'] ?? null, 'caption' => $data['caption'] ?? null, 'credit' => $data['credit'] ?? null, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $asset->addMedia($data['file'])->toMediaCollection('original');

            return $asset;
        });

        return response()->json($asset->load('media'), 201);
    }

    public function update(Request $request, MediaAsset $medium): JsonResponse
    {
        $this->authorizePermission($request);
        $data = $request->validate(['alt_text' => ['nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:2000'], 'credit' => ['nullable', 'string', 'max:255'], 'focal_point' => ['nullable', 'array']]);
        $medium->update([...$data, 'updated_by' => $request->user()->id]);

        return response()->json($medium);
    }

    public function destroy(Request $request, MediaAsset $medium): JsonResponse
    {
        $this->authorizePermission($request);
        abort_if($medium->isInUse(), 422, 'Media in use cannot be deleted.');
        $medium->delete();

        return response()->json(status: 204);
    }

    private function authorizePermission(Request $request): void
    {
        abort_unless($request->user()->can('media.manage'), 403);
    }
}
