<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftNote extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_id',
        'user_id',
        'title',
        'category',
        'visibility',
        'content',
    ];

    /**
     * Get the report that owns the shift note.
     */
    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Get the user that created the shift note.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include notes with specific visibility.
     */
    public function scopeWithVisibility($query, $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope a query to only include notes with specific category.
     */
    public function scopeWithCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
