<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_organization');
    }

    public function view(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('view_organization');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_organization');
    }

    public function update(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('update_organization');
    }

    public function delete(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('delete_organization');
    }

    public function restore(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('restore_organization');
    }

    public function forceDelete(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('force_delete_organization');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_organization');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_organization');
    }

    public function replicate(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('replicate_organization');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_organization');
    }

}