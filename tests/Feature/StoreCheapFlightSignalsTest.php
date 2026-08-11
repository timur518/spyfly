<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\FlightSearchCompleted;
use App\Listeners\StoreCheapFlightSignals;
use App\Models\Alert;
use App\Models\ProfitabilitySetting;
use App\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCheapFlightSignalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_low_price_flights_even_when_percentage_is_below_threshold(): void
    {
        ProfitabilitySetting::current()->update([
            'signal_threshold_percent' => 40,
            'rules' => [],
        ]);

        $searchLog = SearchLog::query()->create([
            'origin_iata' => 'KZN',
            'destination_iata' => 'SVO',
            'search_type' => 'one_way',
            'date_from' => '2026-09-01',
            'date_to' => '2026-12-31',
            'results_count' => 1,
            'status' => 'completed',
            'searched_at' => now(),
        ]);

        $event = new FlightSearchCompleted(
            searchParams: [],
            payload: [
                'analysis' => [
                    'baseline_avg' => 4000,
                    'score' => 88,
                    'best_flights' => [
                        [
                            'price' => 2900,
                            'date' => '2026-09-10',
                            'return_at' => '2026-09-17',
                        ],
                    ],
                ],
            ],
        );
        $event->searchLog = $searchLog;

        (new StoreCheapFlightSignals())->handle($event);

        $this->assertDatabaseHas('alerts', [
            'search_log_id' => $searchLog->id,
            'price' => 2900,
            'baseline_price' => 4000,
        ]);
    }
}
