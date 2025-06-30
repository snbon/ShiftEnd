<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'owner_id',
    ];

    /**
     * Get the owner of the restaurant.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the users that belong to the restaurant.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'restaurant_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get the reports for the restaurant.
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Get the reminders for the restaurant.
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get the shift notes for the restaurant.
     */
    public function shiftNotes()
    {
        return $this->hasManyThrough(ShiftNote::class, Report::class);
    }

    /**
     * Get users with a specific role in this restaurant.
     */
    public function usersWithRole($role)
    {
        return $this->users()->wherePivot('role', $role);
    }

    /**
     * Get managers of this restaurant.
     */
    public function managers()
    {
        return $this->usersWithRole('manager');
    }

    /**
     * Get employees of this restaurant.
     */
    public function employees()
    {
        return $this->usersWithRole('employee');
    }
}
