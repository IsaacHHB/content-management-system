<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminHasTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole(Role::SuperAdmin->value) && ! $request->user()->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('security.edit')->with('status', 'Two-factor authentication is required for super administrators.');
        }

        return $next($request);
    }
}
