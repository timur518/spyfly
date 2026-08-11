<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\FlightSearchCompleted;
use App\Models\SearchLog;
use Carbon\CarbonImmutable;

class RecordSearchHistory
{
    public function handle(FlightSearchCompleted $event): void
    {
        if ($event->searchLogStored && $event->searchLog instanceof SearchLog) {
            return;
        }

        $analysis = $event->payload['analysis'] ?? [];
        $rows = array_merge(
            $event->payload['calendar'] ?? [],
            $event->payload['month_matrix'] ?? [],
            $event->payload['latest'] ?? [],
        );
        $now = CarbonImmutable::now();
        $requestPayload = [
            'search' => $event->searchParams,
            'meta' => $event->payload['meta'] ?? [],
        ];
        $providerPayload = $event->payload['provider_payload'] ?? null;

        $prices = [];
        foreach ($rows as $row) {
            if (isset($row['price']) && (float) $row['price'] > 0) {
                $prices[] = (float) $row['price'];
            }
        }

        $payloadData = [
            'user_id' => $event->userId,
            'origin_iata' => (string) ($event->searchParams['origin'] ?? ''),
            'destination_iata' => (string) ($event->searchParams['destination'] ?? ''),
            'search_type' => ! empty($event->searchParams['one_way']) ? 'one_way' : 'round_trip',
            'date_from' => $event->searchParams['from'] ?? null,
            'date_to' => $event->searchParams['to'] ?? null,
            'max_price' => $prices === [] ? null : max($prices),
            'min_price' => $analysis['overall']['min'] ?? null,
            'median_price' => $analysis['baseline_median'] ?? ($analysis['overall']['median'] ?? null),
            'coverage_percent' => $event->payload['meta']['coverage_percent'] ?? null,
            'results_count' => $analysis['overall']['count'] ?? count($rows),
            'request_payload' => $requestPayload,
            'provider_payload' => $providerPayload,
            'response_summary' => [
                'best_price' => $analysis['best']['price'] ?? null,
                'best_flights_count' => isset($analysis['best_flights']) && is_array($analysis['best_flights']) ? count($analysis['best_flights']) : 0,
                'score' => $analysis['score'] ?? null,
            ],
            'status' => 'completed',
            'searched_at' => CarbonImmutable::now(),
        ];

        $existing = SearchLog::query()
            ->where('origin_iata', (string) ($event->searchParams['origin'] ?? ''))
            ->where('destination_iata', (string) ($event->searchParams['destination'] ?? ''))
            ->where('search_type', ! empty($event->searchParams['one_way']) ? 'one_way' : 'round_trip')
            ->whereDate('date_from', $event->searchParams['from'] ?? null)
            ->whereDate('date_to', $event->searchParams['to'] ?? null)
            ->whereBetween('searched_at', [$now->startOfSecond(), $now->endOfSecond()])
            ->where('request_payload', json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->first();

        if ($existing) {
            $existing->update($payloadData);
            $searchLog = $existing;
        } else {
            $searchLog = SearchLog::create($payloadData);
        }

        $event->searchLog = $searchLog;
        $event->searchLogStored = true;
    }
}
