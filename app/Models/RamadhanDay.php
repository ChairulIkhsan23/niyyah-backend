<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RamadhanDay extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'ramadhan_year',
        'fasting',
        'subuh',
        'dzuhur',
        'ashar',
        'maghrib',
        'isya',
        'tarawih',
        'quran_pages',
        'dzikir_total'
    ];

    /**
     * Relationships user, quran logs, dzikir logs
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quranLogs()
    {
        return $this->hasMany(QuranLog::class);
    }

    public function dzikirLogs()
    {
        return $this->hasMany(DzikirLog::class);
    }
}
