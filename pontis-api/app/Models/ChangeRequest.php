<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ChangeRequest extends Model {
    protected $fillable = ['user_id','field','current_value','new_value','reason','status','reviewer_id','reviewer_notes','reviewed_at'];
    protected function casts(): array { return ['reviewed_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
}
