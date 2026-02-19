<?php

return [
    'base_urls' => [
        'quran' => env('QURAN_API_BASE_URL', 'https://api.quran.com/api/v4'),
        'sholat' => env('SHOLAT_API_BASE_URL', 'https://api.myquran.com/v1'),
        'doa' => env('DOA_API_BASE_URL', 'https://dua-dhikr.vercel.app'),
        'kiblat' => env('KIBLAT_API_BASE_URL', 'https://kiblat-api.vercel.app'),
    ],
    
    'cache_duration' => [
        'quran_surah' => env('CACHE_DURATION_1_MONTH', 2592000), // 30 hari
        'sholat_jadwal' => env('CACHE_DURATION_1_DAY', 86400),   // 24 jam
        'doa' => env('CACHE_DURATION_1_WEEK', 604800),           // 7 hari
        'kiblat' => env('CACHE_DURATION_1_DAY', 86400),          // 24 jam
    ],
    
    'retry_attempts' => 3,
    'timeout' => 30, // seconds
];