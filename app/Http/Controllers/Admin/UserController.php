<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        return response()->json(User::with('roles')->latest()->paginate(30));
    }

    public function show(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        return response()->json($user->load('roles'));
    }

    public function update(Request $request, User $user): JsonResponse
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
            $removesSuper = $locked->hasRole(Role::SuperAdmin->value) && (($data['role'] ?? Role::SuperAdmin->value) !== Role::SuperAdmin->value || ($data['is_active'] ?? true) === false);
            if ($removesSuper) {
                $active = count(User::role(Role::SuperAdmin->value)->where('is_active', true)->lockForUpdate()->get(['users.id'])->all());
                abort_if($active <= 1, 422, 'The last active super administrator cannot be demoted or deactivated.');
            }
            $locked->update(array_intersect_key($data, array_flip(['name', 'is_active'])));
            if (isset($data['role'])) {
                $locked->syncRoles([$data['role']]);
            }
        });

        return response()->json($user->refresh()->load('roles'));
    }
}
