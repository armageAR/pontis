<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Need extends Model {
    use SoftDeletes;
    protected $fillable = ['user_id','service_category_id','title','description','location','urgency','visibility','status','authorized_by','authorized_at','authorization_notes'];
    protected function casts(): array { return ['authorized_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(ServiceCategory::class,'service_category_id'); }
}
