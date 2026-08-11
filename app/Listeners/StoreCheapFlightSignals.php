<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\FlightSearchCompleted;
use App\Models\Alert;
use App\Models\ProfitabilitySetting;

class StoreCheapFlightSignals
{
    public function handle(FlightSearchCompleted $event): void
    {
        if ($event->signalsStored) {
            return;
        }

        $searchLog = $event->searchLog;
        $analysis = $event->payload['analysis'] ?? [];
        $bestFlights = $analysis['best_flights'] ?? [];

        if (! $searchLog || ! is_array($bestFlights) || $bestFlights === []) {
            return;
        }

        $baseline = $analysis['baseline_avg'] ?? $analysis['baseline_median'] ?? null;
        $hasBaseline = is_numeric($baseline) && (float) $baseline > 0;
        if (! $hasBaseline && $bestFlights === []) {
            return;
        }

        $signalThreshold = ProfitabilitySetting::current()->signalThresholdPercent();
        $lowPriceLimit = ProfitabilitySetting::LOW_PRICE_SIGNAL_LIMIT;

        foreach ($bestFlights as $flight) {
            if (! is_array($flight) || ! isset($flight['price'], $flight['date'])) {
                continue;
            }

            $price = (float) $flight['price'];
            $savingPercent = $hasBaseline
                ? (((float) $baseline - $price) / (float) $baseline) * 100
                : null;
            $meetsPercentRule = $hasBaseline && $savingPercent >= $signalThreshold;
            $meetsLowPriceRule = $price < $lowPriceLimit;

            if (! $meetsPercentRule && ! $meetsLowPriceRule) {
                continue;
            }

            $alreadyStored = Alert::query()
                ->where('search_log_id', $searchLog->id)
                ->whereDate('departure_date', $flight['date'])
                ->where('price', $price)
                ->exists();

            if ($alreadyStored) {
                continue;
            }

            Alert::create([
                'search_log_id' => $searchLog->id,
                'origin_iata' => $searchLog->origin_iata,
                'destination_iata' => $searchLog->destination_iata,
                'departure_date' => $flight['date'],
                'return_date' => isset($flight['return_at']) ? substr((string) $flight['return_at'], 0, 10) : null,
                'price' => $price,
                'baseline_price' => $hasBaseline ? (float) $baseline : null,
                'deviation_percent' => $savingPercent !== null ? -$savingPercent : null,
                'score' => $analysis['score'] !== null ? (int) round((float) $analysis['score']) : null,
            ]);
        }

        $event->signalsStored = true;
    }
}
