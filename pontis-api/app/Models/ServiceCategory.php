<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ServiceCategory extends Model {
    protected $fillable = ['name','description','active'];
    protected function casts(): array { return ['active'=>'boolean']; }
    public function services(): HasMany { return $this->hasMany(Service::class); }
    public function needs(): HasMany { return $this->hasMany(Need::class); }
}
