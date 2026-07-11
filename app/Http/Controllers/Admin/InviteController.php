<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Mail\InviteMail;
use App\Models\Invite;
use App\Rules\AllowedEmailDomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InviteController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Invite::with('inviter:id,name')->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => [
                'required', 'email', new AllowedEmailDomain,
                Rule::unique('users', 'email'), Rule::unique('invites', 'pending_email'),
            ],
            'role' => ['required', Rule::enum(Role::class)->only([Role::Admin, Role::Editor])],
        ]);
        $email = Str::lower($data['email']);
        $plainToken = Str::random(64);
        $invite = Invite::create([
            'email' => $email,
            'pending_email' => $email,
            'role' => $data['role'],
            'token' => hash('sha256', $plainToken),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(config('admin.invite_expiry_days')),
        ]);

        Mail::to($invite->email)->queue(new InviteMail($invite, $plainToken));

        return response()->json($invite, 201);
    }

    public function destroy(Invite $invite): JsonResponse
    {
        abort_if($invite->accepted_at !== null, 422, 'Accepted invites are retained for audit history.');
        $invite->delete();

        return response()->json(status: 204);
    }

    public function resend(Invite $invite): JsonResponse
    {
        abort_if($invite->accepted_at !== null, 422, 'Accepted invites cannot be resent.');
        $plainToken = Str::random(64);
        $invite->update([
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(config('admin.invite_expiry_days')),
        ]);
        Mail::to($invite->email)->queue(new InviteMail($invite, $plainToken));

        return response()->json($invite);
    }
}
