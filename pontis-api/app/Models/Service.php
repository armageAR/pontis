<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Service extends Model {
    use SoftDeletes;
    protected $fillable = ['user_id','service_category_id','title','description','modality','location','availability','conditions','visibility','status'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(ServiceCategory::class,'service_category_id'); }
}
