<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Description;
use Illuminate\Auth\Access\HandlesAuthorization;

class DescriptionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Description');
    }

    public function view(AuthUser $authUser, Description $description): bool
    {
        return $authUser->can('View:Description');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Description');
    }

    public function update(AuthUser $authUser, Description $description): bool
    {
        return $authUser->can('Update:Description');
    }

    public function delete(AuthUser $authUser, Description $description): bool
    {
        return $authUser->can('Delete:Description');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Description');
    }

    public function restore(AuthUser $authUser, Description $description): bool
    {
        return $authUser->can('Restore:Description');
    }

    public function forceDelete(AuthUser $authUser, Description $description): bool
    {
        return $authUser->can('ForceDelete:Description');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Description');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Description');
    }

    public function replicate(AuthUser $authUser, Description $description): bool
    {
        return $authUser->can('Replicate:Description');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Description');
    }

}