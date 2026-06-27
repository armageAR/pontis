<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ContactRequest extends Model {
    protected $fillable = ['requester_id','requestee_id','service_id','need_id','message','response_message','status','expires_at'];
    protected function casts(): array { return ['expires_at' => 'datetime']; }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requester_id'); }
    public function requestee(): BelongsTo { return $this->belongsTo(User::class, 'requestee_id'); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function need(): BelongsTo { return $this->belongsTo(Need::class); }
}
