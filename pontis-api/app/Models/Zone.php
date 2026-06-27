<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Zone extends Model {
    protected $fillable = ['name','description','active'];
    public function workshops(): HasMany { return $this->hasMany(Workshop::class); }
}
