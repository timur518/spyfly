<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Airport;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignalsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_latest_signals_for_the_frontend_ticker(): void
    {
        $user = User::factory()->create();

        Airport::query()->create([
            'city' => 'Казань',
            'name' => 'Казань',
            'iata_code' => 'KZN',
            'additional_names' => null,
            'is_popular_destination' => true,
            'is_active' => true,
        ]);

        Airport::query()->create([
            'city' => 'Москва',
            'name' => 'Шереметьево',
            'iata_code' => 'SVO',
            'additional_names' => null,
            'is_popular_destination' => true,
            'is_active' => true,
        ]);

        $roundTripSearchLog = SearchLog::query()->create([
            'user_id' => $user->id,
            'origin_iata' => 'KZN',
            'destination_iata' => 'SVO',
            'search_type' => 'round_trip',
            'date_from' => '2026-09-01',
            'date_to' => '2026-12-31',
            'results_count' => 14,
            'status' => 'completed',
            'searched_at' => now()->subDay(),
        ]);

        $oneWaySearchLog = SearchLog::query()->create([
            'user_id' => $user->id,
            'origin_iata' => 'KZN',
            'destination_iata' => 'SVO',
            'search_type' => 'one_way',
            'date_from' => '2026-09-01',
            'date_to' => '2026-12-31',
            'results_count' => 14,
            'status' => 'completed',
            'searched_at' => now(),
        ]);

        Alert::query()->create([
            'search_log_id' => $roundTripSearchLog->id,
            'origin_iata' => 'KZN',
            'destination_iata' => 'SVO',
            'departure_date' => '2026-09-01',
            'return_date' => '2026-09-10',
            'price' => 2500,
            'baseline_price' => 6000,
            'deviation_percent' => -58,
            'score' => 92,
        ]);

        Alert::query()->create([
            'search_log_id' => $oneWaySearchLog->id,
            'origin_iata' => 'KZN',
            'destination_iata' => 'SVO',
            'departure_date' => '2026-09-01',
            'return_date' => null,
            'price' => 3000,
            'baseline_price' => 7000,
            'deviation_percent' => -57,
            'score' => 89,
        ]);

        Alert::query()->create([
            'search_log_id' => $oneWaySearchLog->id,
            'origin_iata' => 'KZN',
            'destination_iata' => 'SVO',
            'departure_date' => '2026-09-02',
            'return_date' => null,
            'price' => 3000,
            'baseline_price' => 7200,
            'deviation_percent' => -58,
            'score' => 90,
        ]);

        $response = $this->getJson('/api/signals?limit=1');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.route_label', 'Казань - Москва')
            ->assertJsonPath('data.0.price_label', '3 000 ₽')
            ->assertJsonPath('data.0.deviation_label', '-57%');
    }
}
