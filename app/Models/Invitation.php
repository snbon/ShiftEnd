<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'invited_by',
        'email',
        'role',
        'invite_code',
        'expires_at',
        'accepted_at',
        'accepted_by',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the location that the invitation is for.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user who sent the invitation.
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the user who accepted the invitation.
     */
    public function acceptor()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Check if the invitation is expired.
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the invitation can be accepted.
     */
    public function canBeAccepted()
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Generate a unique invite code.
     */
    public static function generateInviteCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('invite_code', $code)->exists());

        return $code;
    }
}
