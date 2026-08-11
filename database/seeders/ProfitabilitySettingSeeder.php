<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProfitabilitySetting;
use Illuminate\Database\Seeder;

class ProfitabilitySettingSeeder extends Seeder
{
    public function run(): void
    {
        ProfitabilitySetting::current()->update([
            'signal_threshold_percent' => 40,
            'rules' => [
                ['from_percent' => 0, 'to_percent' => 5, 'points' => 0],
                ['from_percent' => 5, 'to_percent' => 10, 'points' => 5],
                ['from_percent' => 10, 'to_percent' => 20, 'points' => 10],
                ['from_percent' => 20, 'to_percent' => 35, 'points' => 20],
                ['from_percent' => 35, 'to_percent' => null, 'points' => 30],
            ],
        ]);
    }
}
