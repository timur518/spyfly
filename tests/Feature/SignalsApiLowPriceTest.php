<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignalsApiLowPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_low_price_signals_even_without_big_percentage_discount(): void
    {
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

        Alert::query()->create([
            'search_log_id' => $searchLog->id,
            'origin_iata' => 'KZN',
            'destination_iata' => 'SVO',
            'departure_date' => '2026-09-10',
            'return_date' => null,
            'price' => 2900,
            'baseline_price' => null,
            'deviation_percent' => null,
            'score' => 50,
        ]);

        $response = $this->getJson('/api/signals?limit=1');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price_label', '2 900 ₽')
            ->assertJsonPath('data.0.deviation_label', '—');
    }
}
