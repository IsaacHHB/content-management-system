<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactSubmissionController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('contacts.manage'), 403);

        return Inertia::render('admin/contacts/index', [
            'items' => ContactSubmission::query()->when($request->boolean('unread'), fn ($q) => $q->where('is_read', false))->latest()->paginate(30)->withQueryString(),
            'filters' => ['unread' => $request->boolean('unread')],
            'unread_count' => ContactSubmission::where('is_read', false)->count(),
        ]);
    }

    public function update(Request $request, ContactSubmission $contact): RedirectResponse
    {
        abort_unless($request->user()->can('contacts.manage'), 403);
        $contact->update($request->validate(['is_read' => ['required', 'boolean']]));

        return back()->with('success', 'Updated.');
    }

    public function destroy(Request $request, ContactSubmission $contact): RedirectResponse
    {
        abort_unless($request->user()->can('contacts.manage'), 403);
        $contact->delete();

        return back()->with('success', 'Submission deleted.');
    }
}
