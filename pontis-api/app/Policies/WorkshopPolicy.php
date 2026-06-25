<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workshop;

class WorkshopPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || $user->isAssignedToWorkshop($workshop);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || $user->isAdminOfWorkshop($workshop);
    }

    public function delete(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin();
    }

    public function disable(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || $user->isAdminOfWorkshop($workshop);
    }

    public function enable(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || $user->isAdminOfWorkshop($workshop);
    }

    public function assignUsers(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || $user->isAdminOfWorkshop($workshop);
    }

    public function removeUsers(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || $user->isAdminOfWorkshop($workshop);
    }

    public function viewUsers(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || $user->isAssignedToWorkshop($workshop);
    }

    public function join(User $user, Workshop $workshop): bool
    {
        return $workshop->status === \App\Enums\WorkshopStatus::ACTIVE;
    }

    public function leave(User $user, Workshop $workshop): bool
    {
        return true;
    }

    public function approveMember(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || $user->isAdminOfWorkshop($workshop);
    }
}
