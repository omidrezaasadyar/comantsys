<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SourcingRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class SourcingRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SourcingRequest');
    }

    public function view(AuthUser $authUser, SourcingRequest $sourcingRequest): bool
    {
        return $authUser->can('View:SourcingRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SourcingRequest');
    }

    public function update(AuthUser $authUser, SourcingRequest $sourcingRequest): bool
    {
        return $authUser->can('Update:SourcingRequest');
    }

    public function delete(AuthUser $authUser, SourcingRequest $sourcingRequest): bool
    {
        return $authUser->can('Delete:SourcingRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SourcingRequest');
    }

    public function restore(AuthUser $authUser, SourcingRequest $sourcingRequest): bool
    {
        return $authUser->can('Restore:SourcingRequest');
    }

    public function forceDelete(AuthUser $authUser, SourcingRequest $sourcingRequest): bool
    {
        return $authUser->can('ForceDelete:SourcingRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SourcingRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SourcingRequest');
    }

    public function replicate(AuthUser $authUser, SourcingRequest $sourcingRequest): bool
    {
        return $authUser->can('Replicate:SourcingRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SourcingRequest');
    }

}