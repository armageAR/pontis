<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Need extends Model {
    use SoftDeletes;
    protected $fillable = ['user_id','service_category_id','title','description','location','urgency','visibility','status'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(ServiceCategory::class,'service_category_id'); }
}
