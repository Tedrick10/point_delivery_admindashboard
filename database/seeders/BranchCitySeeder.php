<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class BranchCitySeeder extends Seeder
{
    /**
     * Branch names for OS account / dispatch order forms.
     */
    public function run(): void
    {
        $countryId = City::query()->value('country_id') ?? 1;

        $branches = [
            'Food',
            'MDY',
            'Other City',
            'Yangon',
            'Ygn to Ygn',
        ];

        foreach ($branches as $name) {
            City::firstOrCreate(
                ['name' => $name],
                [
                    'country_id' => $countryId,
                    'status' => 1,
                ]
            );
        }

        City::where('name', 'ChanAyeTharZan')->update(['status' => 0]);
    }
}
