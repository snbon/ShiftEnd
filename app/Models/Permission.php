<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
    ];

    /**
     * Get the role permissions for this permission.
     */
    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * Check if a role has this permission in a specific location.
     */
    public static function roleHasPermission($role, $permissionName, $locationId)
    {
        return self::where('name', $permissionName)
            ->whereHas('rolePermissions', function($query) use ($role, $locationId) {
                $query->where('role', $role)
                      ->where('location_id', $locationId)
                      ->where('granted', true);
            })
            ->exists();
    }

    /**
     * Get all permissions grouped by category.
     */
    public static function getGroupedPermissions()
    {
        return self::orderBy('category')
            ->orderBy('display_name')
            ->get()
            ->groupBy('category');
    }
}
