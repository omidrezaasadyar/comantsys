<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Task;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Task');
    }

    public function view(AuthUser $authUser, Task $task): bool
    {
        // Both sides may view: the assignee and the creator.
        return $authUser->can('View:Task')
            && (
                $task->user_id === $authUser->id
                || $task->created_by === $authUser->id
            );
    }

    public function create(AuthUser $authUser): bool
    {
        // Anyone holding the permission may create — the manager grants it
        // per role/user via the Roles panel.
        return $authUser->can('Create:Task');
    }

    public function update(AuthUser $authUser, Task $task): bool
    {
        // super_admin can override (fix a stuck/wrong task). Otherwise only the
        // ASSIGNEE ticks done / writes the note — a mere creator cannot edit.
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('Update:Task')
            && $task->user_id === $authUser->id;
    }

    public function delete(AuthUser $authUser, Task $task): bool
    {
        // super_admin, or the creator deleting a task they assigned.
        return $authUser->hasRole('super_admin')
            || $task->created_by === $authUser->id;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin');
    }

    public function restore(AuthUser $authUser, Task $task): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, Task $task): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, Task $task): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }
}
