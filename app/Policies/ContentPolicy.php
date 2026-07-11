<?php

namespace App\Policies;

use App\Enums\PublishStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class ContentPolicy
{
    abstract protected function module(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->module().'.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can($this->module().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->module().'.update');
    }

    public function publish(User $user, Model $model): bool
    {
        return $user->can($this->module().'.publish');
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $user->can($this->module().'.delete')) {
            return false;
        }

        if (! $user->hasRole('editor')) {
            return true;
        }

        // Editors may delete only their own content. Content with a draft/publish
        // lifecycle must additionally still be a draft; records without a status
        // (e.g. team members) have no draft state, so ownership is sufficient.
        if ($model->getAttribute('created_by') !== $user->id) {
            return false;
        }

        $status = $model->getAttribute('status');

        return $status === null || $status === PublishStatus::Draft;
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->can('content.restore');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->can('content.force-delete');
    }
}
