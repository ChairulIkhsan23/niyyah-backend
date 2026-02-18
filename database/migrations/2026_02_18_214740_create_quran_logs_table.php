<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quran_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ramadhan_day_id')
                ->constrained('ramadhan_days')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('surah');
            $table->unsignedSmallInteger('ayah')->nullable();

            $table->unsignedSmallInteger('pages')->default(0);
            $table->unsignedSmallInteger('minutes')->default(0);

            $table->timestamps();

            $table->index('surah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_logs');
    }
};
