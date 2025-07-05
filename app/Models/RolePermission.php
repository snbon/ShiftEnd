<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'role',
        'permission_id',
        'granted',
    ];

    protected $casts = [
        'granted' => 'boolean',
    ];

    /**
     * Get the permission that this role permission belongs to.
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Get the location that this role permission belongs to.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
