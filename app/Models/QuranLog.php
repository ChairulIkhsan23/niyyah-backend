<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranLog extends Model
{
    protected $fillable = [
        'ramadhan_day_id',
        'surah',
        'pages',
        'minutes'
    ];

    /**
     * Relationships ramadhan day
    */
    public function ramadhanDay()
    {
        return $this->belongsTo(RamadhanDay::class);
    }

}
