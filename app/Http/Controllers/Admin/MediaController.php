<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Imagick;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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
        if ($isImage && blank($data['alt_text'] ?? null)) {
            throw ValidationException::withMessages(['alt_text' => 'Alternative text is required for images.']);
        }
        if ($isImage) {
            $this->sanitizeImage($data['file']->getRealPath());
        }
        DB::transaction(function () use ($request, $data) {
            $asset = MediaAsset::create(['uuid' => (string) Str::uuid(), 'type' => str_starts_with($data['file']->getMimeType(), 'image/') ? 'image' : 'document', 'original_name' => mb_substr(basename($data['file']->getClientOriginalName()), 0, 255), 'alt_text' => $data['alt_text'] ?? null, 'caption' => $data['caption'] ?? null, 'credit' => $data['credit'] ?? null, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $asset->addMedia($data['file'])->toMediaCollection('original');
        });

        return back()->with('success', 'Media uploaded.');
    }

    public function update(Request $request, MediaAsset $medium): RedirectResponse
    {
        $this->authorizePermission($request);
        $data = $request->validate(['alt_text' => [$medium->type === 'image' ? 'required' : 'nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:2000'], 'credit' => ['nullable', 'string', 'max:255'], 'focal_point' => ['nullable', 'array:x,y'], 'focal_point.x' => ['required_with:focal_point', 'numeric', 'between:0,1'], 'focal_point.y' => ['required_with:focal_point', 'numeric', 'between:0,1']]);
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

    private function sanitizeImage(string $path): void
    {
        $dimensions = @getimagesize($path);
        if ($dimensions === false || ! extension_loaded('imagick')) {
            throw ValidationException::withMessages(['file' => 'The uploaded image could not be securely decoded.']);
        }
        if ($dimensions[0] * $dimensions[1] > 40_000_000) {
            throw ValidationException::withMessages(['file' => 'Images may not exceed 40 megapixels.']);
        }

        try {
            $image = new Imagick($path);
            $image = $image->coalesceImages();

            foreach ($image as $frame) {
                $frame->autoOrient();
                $frame->stripImage();
                $frame->setImageOrientation(Imagick::ORIENTATION_UNDEFINED);
            }

            $image->writeImages($path, true);
            $image->clear();
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'The uploaded image could not be securely processed.']);
        }
    }
}
