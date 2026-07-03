<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class UserDegree extends Model {
    protected $fillable = ['user_id','workshop_id','degree','start_date','end_date','notes','validation_status','validator_id','validated_at','validation_notes'];
    protected function casts(): array {
        return ['start_date'=>'date','end_date'=>'date','validated_at'=>'datetime'];
    }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function workshop(): BelongsTo { return $this->belongsTo(Workshop::class); }
    public function validator(): BelongsTo { return $this->belongsTo(User::class, 'validator_id'); }
}
