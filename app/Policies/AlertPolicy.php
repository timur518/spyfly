<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Alert;
use Illuminate\Auth\Access\HandlesAuthorization;

class AlertPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Alert');
    }

    public function view(AuthUser $authUser, Alert $alert): bool
    {
        return $authUser->can('View:Alert');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Alert');
    }

    public function update(AuthUser $authUser, Alert $alert): bool
    {
        return $authUser->can('Update:Alert');
    }

    public function delete(AuthUser $authUser, Alert $alert): bool
    {
        return $authUser->can('Delete:Alert');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Alert');
    }

    public function restore(AuthUser $authUser, Alert $alert): bool
    {
        return $authUser->can('Restore:Alert');
    }

    public function forceDelete(AuthUser $authUser, Alert $alert): bool
    {
        return $authUser->can('ForceDelete:Alert');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Alert');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Alert');
    }

    public function replicate(AuthUser $authUser, Alert $alert): bool
    {
        return $authUser->can('Replicate:Alert');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Alert');
    }

}