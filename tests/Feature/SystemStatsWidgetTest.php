<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\SystemStatsWidget;
use App\Models\Alert;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class SystemStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_system_stats_for_the_dashboard(): void
    {
        User::factory()->count(2)->create();

        SearchLog::query()->create([
            'origin_iata' => 'KZN',
            'destination_iata' => 'SVO',
            'search_type' => 'one_way',
            'date_from' => '2026-09-01',
            'date_to' => '2026-12-31',
            'results_count' => 1,
            'status' => 'completed',
            'searched_at' => now(),
        ]);

        SearchLog::query()->create([
            'origin_iata' => 'KZN',
            'destination_iata' => 'AER',
            'search_type' => 'one_way',
            'date_from' => '2026-09-02',
            'date_to' => '2026-12-31',
            'results_count' => 1,
            'status' => 'completed',
            'searched_at' => now(),
        ]);

        SearchLog::query()->create([
            'origin_iata' => 'KZN',
            'destination_iata' => 'LED',
            'search_type' => 'round_trip',
            'date_from' => '2026-09-03',
            'date_to' => '2026-12-31',
            'results_count' => 1,
            'status' => 'completed',
            'searched_at' => now(),
        ]);

        Alert::query()->create([
            'search_log_id' => null,
            'origin_iata' => 'KZN',
            'destination_iata' => 'SVO',
            'departure_date' => '2026-09-10',
            'return_date' => null,
            'price' => 2500,
            'baseline_price' => 5000,
            'deviation_percent' => -20,
            'score' => 80,
        ]);

        Alert::query()->create([
            'search_log_id' => null,
            'origin_iata' => 'KZN',
            'destination_iata' => 'AER',
            'departure_date' => '2026-09-11',
            'return_date' => null,
            'price' => 2400,
            'baseline_price' => 5000,
            'deviation_percent' => -30,
            'score' => 82,
        ]);

        Alert::query()->create([
            'search_log_id' => null,
            'origin_iata' => 'KZN',
            'destination_iata' => 'LED',
            'departure_date' => '2026-09-12',
            'return_date' => null,
            'price' => 2300,
            'baseline_price' => 5000,
            'deviation_percent' => -40,
            'score' => 84,
        ]);

        Alert::query()->create([
            'search_log_id' => null,
            'origin_iata' => 'KZN',
            'destination_iata' => 'OVB',
            'departure_date' => '2026-09-13',
            'return_date' => null,
            'price' => 2200,
            'baseline_price' => 5000,
            'deviation_percent' => -50,
            'score' => 86,
        ]);

        $widget = app(SystemStatsWidget::class);
        $method = new ReflectionMethod(SystemStatsWidget::class, 'getStats');
        $method->setAccessible(true);

        $data = array_values($method->invoke($widget));

        self::assertSame('2', $data[0]->getValue());
        self::assertSame('3', $data[1]->getValue());
        self::assertSame('4', $data[2]->getValue());
        self::assertSame('35%', $data[3]->getValue());
    }
}
