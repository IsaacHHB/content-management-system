<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('users.manage'), 403);

        return Inertia::render('admin/users/index', [
            'items' => User::with('roles:id,name')->latest()->paginate(30),
            'assignableRoles' => $request->user()->hasRole(Role::SuperAdmin->value)
                ? ['super_admin', 'admin', 'editor']
                : ['admin', 'editor'],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);
        $assignableRoles = $request->user()->hasRole(Role::SuperAdmin->value)
            ? [Role::SuperAdmin, Role::Admin, Role::Editor]
            : [Role::Admin, Role::Editor];
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:255'], 'role' => ['sometimes', Rule::enum(Role::class)->only($assignableRoles)], 'is_active' => ['sometimes', 'boolean']]);
        if ($user->hasRole(Role::SuperAdmin->value) && ! $request->user()->hasRole(Role::SuperAdmin->value)) {
            abort(403);
        }
        DB::transaction(function () use ($user, $data): void {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            // `is_active` arrives as raw input ("0", "false"), so compare the filtered
            // boolean — a strict `=== false` against a string silently skips the guard.
            $staysActive = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOL);
            $keepsSuper = ($data['role'] ?? Role::SuperAdmin->value) === Role::SuperAdmin->value;
            $removesSuper = $locked->hasRole(Role::SuperAdmin->value) && (! $keepsSuper || ! $staysActive);
            if ($removesSuper) {
                $active = count(User::role(Role::SuperAdmin->value)->where('is_active', true)->lockForUpdate()->get(['users.id'])->all());
                abort_if($active <= 1, 422, 'The last active super administrator cannot be demoted or deactivated.');
            }
            $locked->update(array_intersect_key($data, array_flip(['name', 'is_active'])));
            if (isset($data['role'])) {
                $locked->syncRoles([$data['role']]);
            }
        });

        return back()->with('success', 'User updated.');
    }
}
