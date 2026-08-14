<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PartnerTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class PartnerTransactionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PartnerTransaction');
    }

    public function view(AuthUser $authUser, PartnerTransaction $partnerTransaction): bool
    {
        return $authUser->can('View:PartnerTransaction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PartnerTransaction');
    }

    public function update(AuthUser $authUser, PartnerTransaction $partnerTransaction): bool
    {
        return $authUser->can('Update:PartnerTransaction');
    }

    public function delete(AuthUser $authUser, PartnerTransaction $partnerTransaction): bool
    {
        return $authUser->can('Delete:PartnerTransaction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PartnerTransaction');
    }

    public function restore(AuthUser $authUser, PartnerTransaction $partnerTransaction): bool
    {
        return $authUser->can('Restore:PartnerTransaction');
    }

    public function forceDelete(AuthUser $authUser, PartnerTransaction $partnerTransaction): bool
    {
        return $authUser->can('ForceDelete:PartnerTransaction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PartnerTransaction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PartnerTransaction');
    }

    public function replicate(AuthUser $authUser, PartnerTransaction $partnerTransaction): bool
    {
        return $authUser->can('Replicate:PartnerTransaction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PartnerTransaction');
    }

}