<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedData = config('seed.currencies', []);

        $data = [];

        foreach ($seedData as $currencyData) {
            $data[] = [
                'name' => $currencyData['name'],
                'slug' => Str::slug($currencyData['name']),
                'symbol' => $currencyData['symbol'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('currencies')->insert($data);
    }
}
