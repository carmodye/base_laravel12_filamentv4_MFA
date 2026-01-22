<?php


namespace App\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name', 'parent_id'];

    // Recursive: parent organization
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    // Recursive: child organizations
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // Many-to-many: users
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
