<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Streak extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_active_date'
    ];

    protected $casts = [
        'last_active_date' => 'date'
    ];

    /**
     * Relationships user
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
