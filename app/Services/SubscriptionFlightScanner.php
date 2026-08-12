<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Description;
use App\Models\MonitoringSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SubscriptionFlightScanner
{
    private const LAST_RUN_CACHE_KEY = 'subscription_flight_scanner:last_run_at';

    /**
     * Same shortlist as the UI, so open-ended subscriptions stay practical.
     *
     * @var array<int, string>
     */
    private const POPULAR_DESTINATIONS = [
        'SVO', 'DME', 'VKO', 'LED', 'AER', 'KZN', 'SVX', 'UFA', 'KGD', 'OVB',
        'KRR', 'IST', 'DXB', 'AYT', 'TBS', 'BKK', 'CDG', 'JFK', 'LHR', 'BCN',
    ];

    public function scanDueSubscriptions(bool $force = false): array
    {
        $interval = $this->intervalMinutes();

        if (! $force && $this->recentlyRan($interval)) {
            return [
                'skipped' => true,
                'interval_minutes' => $interval,
                'reason' => 'not_due',
            ];
        }

        $subscriptions = Description::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $summary = [
            'skipped' => false,
            'interval_minutes' => $interval,
            'subscriptions' => $subscriptions->count(),
            'scanned' => 0,
            'updated' => 0,
            'matched' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($subscriptions as $subscription) {
            $summary['scanned']++;

            try {
                $result = $this->scanSubscription($subscription);
                $summary['updated'] += $result['updated'] ? 1 : 0;
                $summary['matched'] += $result['matched_count'];
                $summary['details'][] = $result;
            } catch (Throwable $e) {
                report($e);
                $summary['errors']++;
                $summary['details'][] = [
                    'subscription_id' => $subscription->id,
                    'updated' => false,
                    'matched_count' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Cache::put(self::LAST_RUN_CACHE_KEY, now()->toIso8601String(), now()->addDay());

        return $summary;
    }

    public function scanSubscription(Description $subscription): array
    {
        $window = $this->searchWindow($subscription);
        $destinations = $this->candidateDestinations($subscription);
        $lengths = $this->candidateLengths($subscription, count($destinations) > 1);
        $matches = [];

        foreach ($destinations as $destination) {
            foreach ($lengths as $length) {
                $payload = $this->searchFlights($subscription, $destination, $window, $length);
                $matches = array_merge(
                    $matches,
                    $this->matchesFromPayload($payload, $subscription, $destination)
                );
            }
        }

        $matches = $this->uniqueMatches($matches);
        $subscription->forceFill([
            'matched_flights' => $matches,
        ])->save();

        return [
            'subscription_id' => $subscription->id,
            'updated' => true,
            'matched_count' => count($matches),
            'window' => $window,
            'destinations' => $destinations,
            'lengths' => $lengths,
        ];
    }

    private function intervalMinutes(): int
    {
        return max(1, (int) (MonitoringSetting::current()->subscription_scan_interval_minutes ?? 60));
    }

    private function recentlyRan(int $intervalMinutes): bool
    {
        $lastRunAt = Cache::get(self::LAST_RUN_CACHE_KEY);
        if (! is_string($lastRunAt) || $lastRunAt === '') {
            return false;
        }

        try {
            $lastRun = Carbon::parse($lastRunAt);
        } catch (Throwable) {
            return false;
        }

        return $lastRun->greaterThan(now()->subMinutes($intervalMinutes));
    }

    /**
     * @return array{from:string,to:string}
     */
    private function searchWindow(Description $subscription): array
    {
        $from = $subscription->date_from?->toDateString() ?? now()->toDateString();
        $to = $subscription->date_to?->toDateString() ?? now()->addDays(365)->toDateString();

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function candidateDestinations(Description $subscription): array
    {
        if ($subscription->destination_iata) {
            return [$subscription->destination_iata];
        }

        return array_values(array_filter(
            self::POPULAR_DESTINATIONS,
            static fn (string $destination): bool => $destination !== $subscription->origin_iata
        ));
    }

    /**
     * @return array<int, int>
     */
    private function candidateLengths(Description $subscription, bool $multipleDestinations): array
    {
        if ($subscription->trip_type === 'one_way') {
            return [1];
        }

        $minStay = (int) ($subscription->min_stay_days ?? 0);
        $maxStay = (int) ($subscription->max_stay_days ?? 0);

        if ($minStay <= 0 && $maxStay <= 0) {
            return [7];
        }

        if ($minStay <= 0) {
            $minStay = $maxStay;
        }

        if ($maxStay <= 0) {
            $maxStay = $minStay;
        }

        if ($maxStay < $minStay) {
            [$minStay, $maxStay] = [$maxStay, $minStay];
        }

        if ($multipleDestinations || ($maxStay - $minStay) > 30) {
            $mid = (int) floor(($minStay + $maxStay) / 2);
            return array_values(array_unique([$minStay, $mid, $maxStay]));
        }

        return range($minStay, $maxStay);
    }

    /**
     * @return array<string, mixed>
     */
    private function searchFlights(Description $subscription, string $destination, array $window, int $length): array
    {
        $query = [
            'origin' => $subscription->origin_iata,
            'destination' => $destination,
            'from' => $window['from'],
            'to' => $window['to'],
            'one_way' => $subscription->trip_type === 'one_way' ? 1 : 0,
            'background_scan' => 1,
        ];

        if ($subscription->trip_type !== 'one_way') {
            $query['length'] = $length;
        }

        $response = Http::acceptJson()
            ->timeout(75)
            ->retry(1, 250)
            ->get(url('/api/flights/search'), $query)
            ->throw();

        $payload = $response->json();

        if (! is_array($payload) || ! ($payload['success'] ?? false)) {
            throw new RuntimeException((string) ($payload['error'] ?? 'Не удалось получить рейсы.'));
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    private function matchesFromPayload(array $payload, Description $subscription, string $destination): array
    {
        $bestFlights = data_get($payload, 'analysis.best_flights', []);
        if (! is_array($bestFlights) || $bestFlights === []) {
            return [];
        }

        $matched = [];
        $maxPrice = $subscription->max_desired_price !== null ? (float) $subscription->max_desired_price : null;
        $minStay = $subscription->min_stay_days !== null ? (int) $subscription->min_stay_days : null;
        $maxStay = $subscription->max_stay_days !== null ? (int) $subscription->max_stay_days : null;

        foreach ($bestFlights as $flight) {
            if (! is_array($flight) || ! isset($flight['date'], $flight['price'])) {
                continue;
            }

            $price = (float) $flight['price'];
            if ($maxPrice !== null && $price > $maxPrice) {
                continue;
            }

            if ($subscription->trip_type === 'round_trip') {
                $stayDays = $this->stayDays($flight['date'] ?? null, $flight['return_at'] ?? null);
                if ($stayDays === null) {
                    continue;
                }

                if ($minStay !== null && $stayDays < $minStay) {
                    continue;
                }

                if ($maxStay !== null && $stayDays > $maxStay) {
                    continue;
                }
            }

            $matched[] = [
                'origin_iata' => $subscription->origin_iata,
                'destination_iata' => $destination,
                'trip_type' => $subscription->trip_type,
                'date' => $flight['date'] ?? null,
                'return_at' => $flight['return_at'] ?? null,
                'price' => $price,
                'airline' => $flight['airline'] ?? null,
                'flight_number' => $flight['flight_number'] ?? null,
                'departure_at' => $flight['departure_at'] ?? null,
                'arrival_at' => $flight['arrival_at'] ?? null,
                'duration' => $flight['duration'] ?? null,
                'transfers' => $flight['transfers'] ?? null,
                'stops' => $flight['stops'] ?? [],
                'source' => $flight['source'] ?? 'api/flights/search',
                'link' => $flight['link'] ?? null,
                'matched_at' => now()->toIso8601String(),
            ];
        }

        return $matched;
    }

    /**
     * @param array<int, array<string, mixed>> $matches
     * @return array<int, array<string, mixed>>
     */
    private function uniqueMatches(array $matches): array
    {
        $unique = [];
        $seen = [];

        foreach ($matches as $match) {
            $key = implode('|', [
                $match['origin_iata'] ?? '',
                $match['destination_iata'] ?? '',
                $match['date'] ?? '',
                $match['return_at'] ?? '',
                (string) ($match['price'] ?? ''),
                $match['airline'] ?? '',
                $match['flight_number'] ?? '',
                $match['source'] ?? '',
            ]);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $match;
        }

        usort($unique, static function (array $left, array $right): int {
            $price = ($left['price'] ?? 0) <=> ($right['price'] ?? 0);
            if ($price !== 0) {
                return $price;
            }

            return strcmp((string) ($left['date'] ?? ''), (string) ($right['date'] ?? ''));
        });

        return $unique;
    }

    private function stayDays(mixed $departureDate, mixed $returnAt): ?int
    {
        if (! is_string($departureDate) || ! is_string($returnAt) || $departureDate === '' || $returnAt === '') {
            return null;
        }

        try {
            $depart = Carbon::parse(substr($departureDate, 0, 10));
            $return = Carbon::parse(substr($returnAt, 0, 10));
        } catch (Throwable) {
            return null;
        }

        return max(0, $depart->diffInDays($return, false));
    }
}
