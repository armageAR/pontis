<?php

namespace App\Models;

use App\Enums\WorkshopStatus;
use Database\Factories\WorkshopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workshop extends Model
{
    /** @use HasFactory<WorkshopFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'zone_number',
        'zone_name',
        'name',
        'number',
        'work_day',
        'work_frequency',
        'address',
        'city',
        'province',
        'country',
        'language',
        'status',
        'notes',
        'source_url',
        'last_synced_at',
        'raw_source_text',
    ];

    protected function casts(): array
    {
        return [
            'zone_number' => 'integer',
            'number' => 'integer',
            'status' => WorkshopStatus::class,
            'last_synced_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'status', 'requested_by_user', 'user_seen_at')
            ->withTimestamps()
            ->wherePivot('status', 'active');
    }
}
