<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'location_id',
        'user_id',
        'report_date',
        'shift_start_time',
        'shift_end_time',
        'cash_sales',
        'card_sales',
        'total_sales',
        'opening_cash',
        'closing_cash',
        'cash_difference',
        'tips_cash',
        'tips_card',
        'total_tips',
        'inventory_notes',
        'shift_notes',
        'status',
        'approved_by',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'report_date' => 'date',
        'cash_sales' => 'decimal:2',
        'card_sales' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'tips_cash' => 'decimal:2',
        'tips_card' => 'decimal:2',
        'total_tips' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the location that owns the report.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user that created the report.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift notes for the report.
     */
    public function shiftNotes()
    {
        return $this->hasMany(ShiftNote::class);
    }

    /**
     * Calculate the total turnover.
     */
    public function getTotalTurnoverAttribute()
    {
        return $this->total_cash + $this->total_bank + $this->total_vouchers + $this->total_tips - $this->total_refunds;
    }

    /**
     * Scope a query to only include reports for a specific restaurant.
     */
    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    /**
     * Scope a query to only include reports for a specific date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Get the approver for the report.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
