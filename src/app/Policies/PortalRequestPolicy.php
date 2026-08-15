<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PortalRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * DUAL-AUDIENCE POLICY — the one model in this project that two different
 * kinds of user reach through two different panels.
 *
 * A Laravel policy is registered against the MODEL, not the panel, so this one
 * class governs both App\Filament\Resources\PortalRequests (staff, admin panel)
 * and App\Filament\Portal\Resources\PortalRequests (customers, portal panel).
 * A stock Shield policy would therefore lock external customers out of their own
 * requests the moment it exists: portal accounts hold no roles and no
 * permissions, so every `can('…:PortalRequest')` returns false.
 *
 * Hence the `is_portal_user` early-return on the four abilities a customer
 * genuinely needs. It is NOT a hole:
 *
 *   - `is_portal_user` is set by staff on the User record; a customer cannot
 *     grant it to themselves, and User::canAccessPanel() makes it absolute —
 *     a portal account can never reach the admin panel, role or no role.
 *   - Row-level safety comes from the portal resource's owner scope
 *     (`where('user_id', auth()->id())` in the portal PortalRequestResource),
 *     so `view`/`update` can only ever be asked about a row that already
 *     belongs to the caller. This policy answers "may this audience do this
 *     kind of thing", the scope answers "to which rows".
 *   - Destructive abilities are deliberately NOT granted below. Customers
 *     never delete, restore, replicate or reorder; those stay permission-only.
 *
 * super_admin needs nothing here: this project sets `define_via_gate = false`
 * in config/filament-shield.php, so there is no Gate::before bypass — instead
 * shield:generate hands every new permission to the super_admin role directly
 * (Utils::giveSuperAdminPermission), and it passes through the normal
 * `$authUser->can(...)` branch like any other role. Do not add a bypass here.
 *
 * Permission strings are exactly what shield:generate produced for this model
 * (Ability:Model, e.g. `ViewAny:PortalRequest`) — the same convention as
 * InquiryPolicy and every other policy in app/Policies.
 */
class PortalRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        // Portal customer: their own list. Ownership is enforced by the portal
        // resource's query scope, not here.
        if ($authUser->is_portal_user) {
            return true;
        }

        return $authUser->can('ViewAny:PortalRequest');
    }

    public function view(AuthUser $authUser, PortalRequest $portalRequest): bool
    {
        // The portal resource never hands this policy a row the customer does
        // not own — the owner scope filters them out before authorization runs.
        if ($authUser->is_portal_user) {
            return true;
        }

        return $authUser->can('View:PortalRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        // Submitting a request is the entire point of the portal. Staff create
        // nothing here — the admin resource has no create page.
        if ($authUser->is_portal_user) {
            return true;
        }

        return $authUser->can('Create:PortalRequest');
    }

    public function update(AuthUser $authUser, PortalRequest $portalRequest): bool
    {
        // Same scope guarantee as view(). What a customer may actually change
        // is bounded by the portal FORM, which exposes no status or response
        // field; the admin form in turn disables the customer's own columns.
        if ($authUser->is_portal_user) {
            return true;
        }

        return $authUser->can('Update:PortalRequest');
    }

    // ── Below: staff only. No is_portal_user branch, on purpose. ──

    public function delete(AuthUser $authUser, PortalRequest $portalRequest): bool
    {
        return $authUser->can('Delete:PortalRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PortalRequest');
    }

    public function restore(AuthUser $authUser, PortalRequest $portalRequest): bool
    {
        return $authUser->can('Restore:PortalRequest');
    }

    public function forceDelete(AuthUser $authUser, PortalRequest $portalRequest): bool
    {
        return $authUser->can('ForceDelete:PortalRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PortalRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PortalRequest');
    }

    public function replicate(AuthUser $authUser, PortalRequest $portalRequest): bool
    {
        return $authUser->can('Replicate:PortalRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PortalRequest');
    }
}
