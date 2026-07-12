<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use App\Rules\AllowedEmailDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInviteController extends Controller
{
    public function show(string $token): Response
    {
        $invite = $this->findValidInvite($token);

        return Inertia::render('auth/accept-invite', [
            'email' => $invite->email,
            'acceptUrl' => URL::temporarySignedRoute(
                'invite.accept',
                $invite->expires_at,
                ['token' => $token],
            ),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $inviteEmail = $this->findValidInvite($token)->email;
        $data = $request->merge(['email' => $inviteEmail])->validate([
            'email' => ['required', 'email', new AllowedEmailDomain],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $user = DB::transaction(function () use ($data, $token): User {
            $invite = Invite::query()
                ->where('token', hash('sha256', $token))
                ->pending()
                ->lockForUpdate()
                ->firstOrFail();

            $user = User::create([
                'name' => $data['name'],
                'email' => $invite->email,
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);
            $user->assignRole($invite->role->value);
            $invite->update(['accepted_at' => now(), 'pending_email' => null]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    private function findValidInvite(string $token): Invite
    {
        return Invite::query()
            ->where('token', hash('sha256', $token))
            ->pending()
            ->firstOrFail();
    }
}
