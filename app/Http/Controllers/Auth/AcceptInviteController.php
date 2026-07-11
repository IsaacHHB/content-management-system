<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use App\Rules\AllowedEmailDomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class AcceptInviteController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $invite = $this->findValidInvite($token);

        return response()->json([
            'email' => $invite->email,
            'expires_at' => $invite->expires_at,
            'accept_url' => URL::temporarySignedRoute(
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
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
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
