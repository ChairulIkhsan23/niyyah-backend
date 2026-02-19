<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QuranApiService;
use App\Services\DoaApiService;

class WarmupIslamicCache extends Command
{
    protected $signature = 'islamic:warmup-cache';
    protected $description = 'Warmup cache for Islamic APIs';
    
    protected $quranService;
    protected $doaService;
    
    public function __construct(
        QuranApiService $quranService,
        DoaApiService $doaService
    ) {
        parent::__construct();
        $this->quranService = $quranService;
        $this->doaService = $doaService;
    }
    
    public function handle()
    {
        $this->info('Warming up Quran cache...');
        $this->quranService->getAllSurah();
        
        $this->info('Warming up Doa cache...');
        $this->doaService->getDailyPrayers();
        $this->doaService->getMorningEveningPrayers();
        
        $this->info('Cache warmup completed!');
    }
}