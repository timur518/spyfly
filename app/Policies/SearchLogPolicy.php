<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SearchLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class SearchLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SearchLog');
    }

    public function view(AuthUser $authUser, SearchLog $searchLog): bool
    {
        return $authUser->can('View:SearchLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SearchLog');
    }

    public function update(AuthUser $authUser, SearchLog $searchLog): bool
    {
        return $authUser->can('Update:SearchLog');
    }

    public function delete(AuthUser $authUser, SearchLog $searchLog): bool
    {
        return $authUser->can('Delete:SearchLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SearchLog');
    }

    public function restore(AuthUser $authUser, SearchLog $searchLog): bool
    {
        return $authUser->can('Restore:SearchLog');
    }

    public function forceDelete(AuthUser $authUser, SearchLog $searchLog): bool
    {
        return $authUser->can('ForceDelete:SearchLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SearchLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SearchLog');
    }

    public function replicate(AuthUser $authUser, SearchLog $searchLog): bool
    {
        return $authUser->can('Replicate:SearchLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SearchLog');
    }

}