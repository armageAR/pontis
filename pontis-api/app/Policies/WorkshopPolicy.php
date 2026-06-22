<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workshop;

class WorkshopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function view(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || ($user->isAdmin() && $user->isAssignedToWorkshop($workshop));
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || ($user->isAdmin() && $user->isAssignedToWorkshop($workshop));
    }

    public function delete(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin();
    }

    public function disable(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin();
    }

    public function enable(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin();
    }

    public function assignUsers(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || ($user->isAdmin() && $user->isAssignedToWorkshop($workshop));
    }

    public function removeUsers(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || ($user->isAdmin() && $user->isAssignedToWorkshop($workshop));
    }

    public function viewUsers(User $user, Workshop $workshop): bool
    {
        return $user->isSuperAdmin() || ($user->isAdmin() && $user->isAssignedToWorkshop($workshop));
    }
}
