<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Need extends Model {
    use SoftDeletes;
    protected $fillable = ['user_id','service_category_id','title','description','location','urgency','visibility','status','published_at','expires_at','authorized_by','authorized_at','authorization_notes'];
    protected $appends = ['effective_status'];
    protected function casts(): array { return ['authorized_at' => 'datetime', 'published_at' => 'datetime', 'expires_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(ServiceCategory::class,'service_category_id'); }

    /** Estado efectivo para la UI: una publicación activa vencida se muestra como "expired". */
    public function getEffectiveStatusAttribute(): string {
        if ($this->status === 'active' && $this->expires_at !== null && $this->expires_at->isPast()) {
            return 'expired';
        }
        return $this->status;
    }
}
