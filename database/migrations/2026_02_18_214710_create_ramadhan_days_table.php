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
        Schema::create('ramadhan_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');
            $table->year('ramadhan_year')->index(); // multi ramadhan support

            // Core ibadah
            $table->boolean('fasting')->default(false);

            $table->boolean('subuh')->default(false);
            $table->boolean('dzuhur')->default(false);
            $table->boolean('ashar')->default(false);
            $table->boolean('maghrib')->default(false);
            $table->boolean('isya')->default(false);
            $table->boolean('tarawih')->default(false);

            // Aggregated metrics
            $table->unsignedSmallInteger('quran_pages')->default(0);
            $table->unsignedInteger('dzikir_total')->default(0);

            $table->timestamps();

            // Prevent duplicate per day
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ramadhan_days');
    }
};
