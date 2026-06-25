<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class)
            ->withPivot('role', 'status', 'requested_by_user', 'user_seen_at')
            ->withTimestamps()
            ->wherePivot('status', 'active');
    }

    public function workshopMemberships(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class)
            ->withPivot('role', 'status', 'requested_by_user', 'user_seen_at')
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === UserStatus::PENDING;
    }

    public function isAssignedToWorkshop(Workshop $workshop): bool
    {
        return $this->workshops()->where('workshop_id', $workshop->id)->exists();
    }

    public function isAdminOfWorkshop(Workshop $workshop): bool
    {
        return $this->workshops()
            ->where('workshop_id', $workshop->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function isAdminOfAnyWorkshop(): bool
    {
        return $this->workshops()->wherePivot('role', 'admin')->exists();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
