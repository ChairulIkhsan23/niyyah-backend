<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'user_id',
        'surah',
        'ayah',
        'page'
    ];

    /**
     * Relationships user, ramadhan day
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ramadhanDay()
    {
        return $this->belongsTo(RamadhanDay::class);
    }
}
