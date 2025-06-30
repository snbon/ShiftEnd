<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'location_id',
        'default_location_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the location that the user is assigned to.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user's default location.
     */
    public function defaultLocation()
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    /**
     * Get the locations that the user owns.
     */
    public function ownedLocations()
    {
        return $this->hasMany(Location::class, 'owner_id');
    }

    /**
     * Get the restaurants that the user belongs to.
     */
    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'restaurant_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get the reports created by the user.
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Get the shift notes created by the user.
     */
    public function shiftNotes()
    {
        return $this->hasMany(ShiftNote::class);
    }

    /**
     * Get the activity logs for the user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the reminders for the user.
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Check if user is an owner.
     */
    public function isOwner()
    {
        return $this->hasRole('owner');
    }

    /**
     * Check if user is a manager.
     */
    public function isManager()
    {
        return $this->hasRole('manager');
    }

    /**
     * Check if user is an employee.
     */
    public function isEmployee()
    {
        return $this->hasRole('employee');
    }

    /**
     * Check if user is pending (waiting for invitation).
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if user is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is inactive.
     */
    public function isInactive()
    {
        return $this->status === 'inactive';
    }

    /**
     * Check if user is the first user (should be owner).
     */
    public static function isFirstUser()
    {
        return self::count() === 0;
    }

    /**
     * Get invitations sent by this user.
     */
    public function sentInvitations()
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    /**
     * Get invitations received by this user.
     */
    public function receivedInvitations()
    {
        return $this->hasMany(Invitation::class, 'accepted_by');
    }
}
