<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RamadhanDay;
use Carbon\Carbon;

class RamadhanSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $start = Carbon::create(2026, 2, 18); // contoh awal ramadhan

        for ($i = 0; $i < 30; $i++) {
            RamadhanDay::create([
                'user_id' => $user->id,
                'date' => $start->copy()->addDays($i),
                'ramadhan_year' => 2026
            ]);
        }
    }
}
