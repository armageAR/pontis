<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\ChangeRequest;
use App\Models\ContactRequest;
use App\Models\Need;
use App\Models\PontisNotification;
use App\Models\Service;
use App\Models\UserDegree;
use App\Models\UserPosition;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'last_name', 'email', 'password', 'role', 'status',
        'dni', 'masonic_id', 'birth_date', 'initiation_date', 'masonic_status',
        'phone', 'whatsapp', 'alternative_email', 'contact_preference',
        'country', 'province', 'locality', 'neighborhood', 'address',
        'profession', 'occupation', 'company', 'profession_description', 'bio',
        'photo_url', 'linkedin', 'website', 'facebook', 'instagram', 'availability_notes', 'admin_notes',
        'phone_fixed', 'secondary_activities', 'knowledge_areas', 'certifications',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'birth_date' => 'date',
            'initiation_date' => 'date',
        ];
    }

    public function userDegrees(): HasMany { return $this->hasMany(UserDegree::class); }
    public function userPositions(): HasMany { return $this->hasMany(UserPosition::class); }
    public function services(): HasMany { return $this->hasMany(Service::class); }
    public function needs(): HasMany { return $this->hasMany(Need::class); }
    public function changeRequests(): HasMany { return $this->hasMany(ChangeRequest::class); }
    public function sentContactRequests(): HasMany { return $this->hasMany(ContactRequest::class, 'requester_id'); }
    public function receivedContactRequests(): HasMany { return $this->hasMany(ContactRequest::class, 'requestee_id'); }
    public function pontisNotifications(): HasMany { return $this->hasMany(PontisNotification::class); }

    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class)
            ->withPivot('role', 'status', 'requested_by_user', 'user_seen_at', 'correction_notes', 'is_principal')
            ->withTimestamps()
            ->wherePivot('status', 'active');
    }

    public function workshopMemberships(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class)
            ->withPivot('role', 'status', 'requested_by_user', 'user_seen_at', 'correction_notes', 'is_principal')
            ->withTimestamps();
    }

    /** ID del taller principal (membresía activa marcada como principal), o null. */
    public function principalWorkshopId(): ?int
    {
        return $this->workshops()->wherePivot('is_principal', true)->value('workshops.id');
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
