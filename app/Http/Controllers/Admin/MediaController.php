<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $this->authorizePermission($request);

        $media = MediaAsset::query()
            ->with('media')
            ->withCount('references')
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('original_name', 'like', "%{$s}%")->orWhere('alt_text', 'like', "%{$s}%")))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        // AJAX (media picker) gets JSON; a browser/Inertia visit gets the page.
        if ($request->wantsJson()) {
            return response()->json($media);
        }

        return Inertia::render('admin/media/index', [
            'items' => $media,
            'filters' => ['search' => $request->string('search')->toString(), 'type' => $request->string('type')->toString()],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request);
        $data = $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:25600'], 'alt_text' => ['nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:2000'], 'credit' => ['nullable', 'string', 'max:255']]);
        $isImage = str_starts_with((string) $data['file']->getMimeType(), 'image/');
        abort_if($isImage && $data['file']->getSize() > 15 * 1024 * 1024, 422, 'Images may not exceed 15 MB.');
        DB::transaction(function () use ($request, $data) {
            $asset = MediaAsset::create(['uuid' => (string) Str::uuid(), 'type' => str_starts_with($data['file']->getMimeType(), 'image/') ? 'image' : 'document', 'original_name' => $data['file']->getClientOriginalName(), 'alt_text' => $data['alt_text'] ?? null, 'caption' => $data['caption'] ?? null, 'credit' => $data['credit'] ?? null, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $asset->addMedia($data['file'])->toMediaCollection('original');
        });

        return back()->with('success', 'Media uploaded.');
    }

    public function update(Request $request, MediaAsset $medium): RedirectResponse
    {
        $this->authorizePermission($request);
        $data = $request->validate(['alt_text' => ['nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:2000'], 'credit' => ['nullable', 'string', 'max:255'], 'focal_point' => ['nullable', 'array']]);
        $medium->update([...$data, 'updated_by' => $request->user()->id]);

        return back()->with('success', 'Media updated.');
    }

    public function destroy(Request $request, MediaAsset $medium): RedirectResponse
    {
        $this->authorizePermission($request);
        abort_if($medium->isInUse(), 422, 'Media in use cannot be deleted.');
        $medium->delete();

        return back()->with('success', 'Media deleted.');
    }

    private function authorizePermission(Request $request): void
    {
        abort_unless($request->user()->can('media.manage'), 403);
    }
}
