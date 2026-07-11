<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactSubmissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('contacts.manage'), 403);

        return response()->json(ContactSubmission::query()->when($request->boolean('unread'), fn ($q) => $q->where('is_read', false))->latest()->paginate(30));
    }

    public function update(Request $request, ContactSubmission $contact): JsonResponse
    {
        abort_unless($request->user()->can('contacts.manage'), 403);
        $contact->update($request->validate(['is_read' => ['required', 'boolean']]));

        return response()->json($contact);
    }

    public function destroy(Request $request, ContactSubmission $contact): JsonResponse
    {
        abort_unless($request->user()->can('contacts.manage'), 403);
        $contact->delete();

        return response()->json(status: 204);
    }
}
