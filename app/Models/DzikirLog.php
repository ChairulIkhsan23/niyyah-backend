<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DzikirLog extends Model
{
    protected $fillable = [
        'ramadhan_day_id',
        'dzikir_name',
        'count'
    ];
    
    /**
     * Relationships ramadhan day
    */
    public function ramadhanDay()
    {
        return $this->belongsTo(RamadhanDay::class);
    }

}
