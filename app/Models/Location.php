<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'owner_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // The owner of the location
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Users assigned to this location
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Reports for this location
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
