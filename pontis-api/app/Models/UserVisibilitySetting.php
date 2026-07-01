<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class UserVisibilitySetting extends Model {
    protected $fillable = ['user_id','block','visibility','anonymous_search'];
    protected $casts = ['anonymous_search' => 'boolean'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
