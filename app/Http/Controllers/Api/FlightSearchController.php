<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\FlightSearchCompleted;
use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use App\Models\ProfitabilitySetting;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class FlightSearchController extends Controller
{
    /**
     * UTC offsets used to convert link timestamps back to local airport time.
     *
     * @var array<string, int>
     */
    private const AIRPORT_UTC_OFFSETS = [
        'SVO' => 3, 'DME' => 3, 'VKO' => 3, 'LED' => 3, 'AER' => 3, 'KZN' => 3,
        'ROV' => 3, 'KUF' => 4, 'UFA' => 5, 'KGD' => 2, 'MRV' => 3, 'KRR' => 3,
        'GOJ' => 3, 'ARH' => 3, 'MMK' => 3, 'SVX' => 5, 'CEK' => 5, 'PEE' => 5,
        'TJM' => 5, 'OMS' => 6, 'OVB' => 7, 'NOZ' => 7, 'ABA' => 7, 'KJA' => 7,
        'IKT' => 8, 'YKS' => 9, 'HTA' => 9, 'BQS' => 9, 'VVO' => 10, 'PKC' => 12,
    ];

    public function search(Request $request): JsonResponse
    {
        try {
            $config = $this->travelpayoutsConfig();
            $params = $this->validateInput($request);

            $payload = $this->buildSearchPayload($params, $config);
            if (! $request->boolean('background_scan')) {
                event(new FlightSearchCompleted($params, $payload, $request->user()?->id));
            }

            return response()->json(['success' => true] + $payload);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    private function travelpayoutsConfig(): array
    {
        $settings = IntegrationSetting::current();

        if (blank($settings->travelpayouts_api_base) || blank($settings->travelpayouts_api_token)) {
            throw new RuntimeException('Travelpayouts settings are missing.');
        }

        return [
            'api_base' => $settings->travelpayouts_api_base,
            'api_token' => $settings->travelpayouts_api_token,
            'partner_id' => $settings->travelpayouts_partner_id,
            'tp_trs' => $settings->travelpayouts_tp_trs,
            'tp_p' => $settings->travelpayouts_tp_p,
        ];
    }

    private function validateInput(Request $request): array
    {
        $validated = Validator::make($request->query(), [
            'origin' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'destination' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'one_way' => ['sometimes', 'boolean'],
            'direct' => ['sometimes', 'boolean'],
            'length' => ['sometimes', 'integer', 'min:1', 'max:370'],
        ])->validate();

        $oneWay = (bool) ($validated['one_way'] ?? false);
        $direct = (bool) ($validated['direct'] ?? false);
        $length = (int) ($validated['length'] ?? ($oneWay ? 1 : 7));

        if (! $oneWay && $length > 370) {
            throw new InvalidArgumentException('Диапазон ограничен 370 днями.');
        }

        return [
            'origin' => $validated['origin'],
            'destination' => $validated['destination'],
            'from' => $validated['from'],
            'to' => $validated['to'],
            'one_way' => $oneWay,
            'direct' => $direct,
            'length' => $length,
        ];
    }

    private function buildSearchPayload(array $params, array $config): array
    {
        $origin = $params['origin'];
        $destination = $params['destination'];
        $from = $params['from'];
        $to = $params['to'];
        $oneWay = $params['one_way'];
        $direct = $params['direct'];
        $length = $params['length'];

        $fromDate = new DateTimeImmutable($from);
        $toDate = new DateTimeImmutable($to);

        $months = $this->monthsBetween($fromDate, $toDate);
        $calendarRows = [];
        $matrixRows = [];
        $latestRows = [];
        $requests = [];
        $droppedByTripLength = [];
        $scannedFlightsExtra = 0;
        $legPrices = null;

        if ($oneWay) {
            foreach ($months as $month) {
                $matrix = $this->tpRequest('/v2/prices/month-matrix', [
                    'origin' => $origin,
                    'destination' => $destination,
                    'month' => $month . '-01',
                    'currency' => 'RUB',
                    'show_to_affiliates' => 'true',
                ], $config);

                $matrixRows = array_merge($matrixRows, $this->normalizeMatrix($matrix, $from, $to));
                $requests[] = ['method' => 'v2/prices/month-matrix', 'month' => $month, 'ok' => true];

                $page = 1;
                $limit = 1000;
                do {
                    $latest = $this->tpRequest('/v2/prices/latest', [
                        'origin' => $origin,
                        'destination' => $destination,
                        'currency' => 'RUB',
                        'period_type' => 'month',
                        'beginning_of_period' => $month . '-01',
                        'page' => $page,
                        'limit' => $limit,
                        'show_to_affiliates' => 'true',
                        'sorting' => 'price',
                        'trip_class' => 0,
                        'one_way' => 'true',
                        'trip_duration' => null,
                    ], $config);

                    $latestRows = array_merge($latestRows, $this->normalizeLatest($latest, $from, $to));
                    $requests[] = ['method' => 'v2/prices/latest', 'month' => $month, 'page' => $page, 'ok' => true];

                    $count = isset($latest['data']) && is_array($latest['data']) ? count($latest['data']) : 0;
                    $page++;
                } while ($count === $limit && $page <= 20);
            }
        } else {
            // Собираем маршрут "туда" и "обратно" отдельно как one-way перелёты,
            // а затем сами склеиваем самые дешёвые пары под нужную длительность поездки —
            // это даёт намного больше вариантов, чем готовые round-trip предложения провайдера.
            $extendedToDate = $toDate->modify('+' . ($length + 1) . ' days');
            $backMonths = $this->monthsBetween($fromDate, $extendedToDate);

            $outRows = $this->fetchOneWayLegRows($origin, $destination, $months, $from, $to, $config, $requests);
            $backRows = $this->fetchOneWayLegRows($destination, $origin, $backMonths, $from, $extendedToDate->format('Y-m-d'), $config, $requests);

            if ($direct) {
                $outRows = $this->filterDirectRows($outRows);
                $backRows = $this->filterDirectRows($backRows);
            }

            $scannedFlightsExtra = count($outRows) + count($backRows);
            $matrixRows = $this->buildRoundTripPairs($outRows, $backRows, $length, $from, $to);

            // Отдельные ряды "туда" и "обратно" для двух линий на графике —
            // в отличие от $matrixRows (уже склеенные пары), здесь цена только за одну "ногу".
            $legPrices = [
                'out' => array_map(
                    static fn (array $r): array => ['date' => $r['date'], 'price' => $r['price']],
                    $this->bestByDate($outRows),
                ),
                'back' => array_map(
                    static fn (array $r): array => ['date' => $r['date'], 'price' => $r['price']],
                    $this->bestByDate($backRows),
                ),
            ];
        }

        $calendarRows = $this->withRouteDefaults(
            $this->filterRowsByTripMode($calendarRows, $oneWay, $length, $droppedByTripLength),
            $origin,
            $destination,
        );
        $matrixRows = $this->withRouteDefaults(
            $this->filterRowsByTripMode($matrixRows, $oneWay, $length, $droppedByTripLength),
            $origin,
            $destination,
        );
        $latestRows = $this->withRouteDefaults(
            $this->filterRowsByTripMode($latestRows, $oneWay, $length, $droppedByTripLength),
            $origin,
            $destination,
        );

        if ($direct) {
            $calendarRows = $this->filterDirectRows($calendarRows);
            $matrixRows = $this->filterDirectRows($matrixRows);
            $latestRows = $this->filterDirectRows($latestRows);
        }

        $sourceRows = array_merge($calendarRows, $matrixRows, $latestRows);
        $allRows = $this->enrichRows(
            $this->bestByDate($sourceRows),
            $sourceRows,
        );
        usort($allRows, static fn (array $a, array $b): int => $a['price'] <=> $b['price']);

        $overallStats = $this->stats($allRows);
        $latestStats = $this->stats($latestRows);
        $best = $allRows[0] ?? null;
        $avgBaseline = $overallStats['avg'];
        $baselineMedian = $overallStats['median'];
        $profitabilityRules = $this->profitabilityRules();
        $bestFlights = $this->enrichBestFlights(
            $allRows,
            $best,
            $origin,
            $destination,
            $oneWay,
            $length,
            $direct,
            $config,
            $requests,
        );
        $savingPercent = null;
        $score = null;
        $classification = 'Нет достаточных данных';

        if ($best && $avgBaseline && $avgBaseline > 0) {
            $savingPercent = max(0, (($avgBaseline - $best['price']) / $avgBaseline) * 100);
            $coverageDays = count(array_unique(array_column($allRows, 'date')));
            $totalDays = $fromDate->diff($toDate)->days + 1;
            $coverage = $totalDays ? $coverageDays / $totalDays : 0;

            $baseScore = $this->scoreFromRules($savingPercent, $profitabilityRules);
            if ($baseScore === null) {
                $baseScore = 50 + ($savingPercent * 1.5);
            }

            $score = min(100, max(0, $baseScore * (0.55 + 0.45 * $coverage)));

            if ($savingPercent >= 35 && $coverage >= 0.5) {
                $classification = '🔥 Аномально дёшево';
            } elseif ($savingPercent >= 25) {
                $classification = '🚀 Очень выгодно';
            } elseif ($savingPercent >= 15) {
                $classification = '🟢 Выгодно';
            } elseif ($savingPercent >= 7) {
                $classification = '🟡 Ниже обычного';
            } else {
                $classification = '⚪ Обычная цена';
            }
        }

        $totalDays = $fromDate->diff($toDate)->days + 1;
        $coveredDays = count(array_unique(array_column($allRows, 'date')));
        $coveragePercent = $totalDays ? ($coveredDays / $totalDays) * 100 : 0;
        $missingDates = $this->missingDatesInPeriod($allRows, $fromDate, $toDate);

        return [
            'meta' => [
                'origin' => $origin,
                'destination' => $destination,
                'from' => $from,
                'to' => $to,
                'one_way' => $oneWay,
                'direct' => $direct,
                'length' => $oneWay ? null : $length,
                'months' => $months,
                'total_days' => $totalDays,
                'covered_days' => $coveredDays,
                'scanned_flights' => $oneWay
                    ? count($calendarRows) + count($matrixRows) + count($latestRows)
                    : $scannedFlightsExtra + count($matrixRows),
                'coverage_percent' => $coveragePercent,
                'generated_at' => gmdate('c'),
                'partner_id' => $config['partner_id'],
                'buy_prefix' => $this->buildBuyPrefix($config),
                'cache_note' => 'Travelpayouts Data API отдаёт данные из кэша; отсутствие строки не означает отсутствие рейса.',
            ],
            'analysis' => [
                'classification' => $classification,
                'score' => $score,
                'best' => $best,
                'best_flights' => $bestFlights,
                'baseline_median' => $baselineMedian,
                'baseline_avg' => $avgBaseline,
                'saving_percent' => $savingPercent,
                'overall' => $overallStats,
                'latest_48h' => $latestStats,
            ],
            'provider_payload' => [
                'calendar' => $calendarRows,
                'month_matrix' => $matrixRows,
                'latest' => $latestRows,
            ],
            'coverage' => $this->monthCoverage($allRows, $months, $fromDate, $toDate),
            'calendar' => $this->bestByDate($calendarRows),
            'month_matrix' => $this->bestByDate($matrixRows),
            'latest' => $this->bestByDate($latestRows),
            'leg_prices' => $legPrices,
            'diagnostics' => [
                'missing_dates_count' => count($missingDates),
                'missing_dates_sample' => array_slice($missingDates, 0, 31),
                'source_coverage' => [
                    'v1/calendar' => $this->sourceCoverage($calendarRows, $fromDate, $toDate),
                    'v2/month-matrix' => $this->sourceCoverage($matrixRows, $fromDate, $toDate),
                    'v2/latest' => $this->sourceCoverage($latestRows, $fromDate, $toDate),
                ],
                'dropped_by_trip_length' => $droppedByTripLength,
            ],
            'requests' => $requests,
        ];
    }

    private function tpRequest(string $path, array $query, array $config): array
    {
        $url = rtrim($config['api_base'], '/') . $path . '?' . http_build_query(
                array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '')
            );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 35,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'X-Access-Token: ' . $config['api_token'],
                'Accept: application/json',
                'Accept-Encoding: gzip, deflate',
            ],
            CURLOPT_USERAGENT => 'Spyfly/1.0',
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new RuntimeException("cURL #{$errno}: {$error}");
        }

        $json = json_decode((string) $body, true);
        if (! is_array($json)) {
            throw new RuntimeException("Travelpayouts вернул не JSON (HTTP {$status}).");
        }

        if ($status >= 400 || (($json['success'] ?? true) === false)) {
            $message = $json['error'] ?? "HTTP {$status}";
            throw new RuntimeException("Travelpayouts: {$message}");
        }

        return $json;
    }

    /**
     * @return array<int, string>
     */
    private function monthsBetween(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $cursor = $from->modify('first day of this month');
        $last = $to->modify('first day of this month');
        $months = [];

        while ($cursor <= $last) {
            $months[] = $cursor->format('Y-m');
            $cursor = $cursor->modify('+1 month');
        }

        return $months;
    }

    /**
     * @param array<string, mixed> $json
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCalendar(array $json, string $from, string $to): array
    {
        $rows = [];

        foreach (($json['data'] ?? []) as $date => $item) {
            if (! is_array($item) || ! isset($item['price'])) {
                continue;
            }

            $rowDate = substr((string) ($item['departure_at'] ?? $date), 0, 10);
            if ($rowDate < $from || $rowDate > $to) {
                continue;
            }

            $rows[] = [
                'date' => $rowDate,
                'price' => (float) $item['price'],
                'origin' => $item['origin'] ?? null,
                'destination' => $item['destination'] ?? null,
                'transfers' => isset($item['transfers']) ? (int) $item['transfers'] : null,
                'airline' => $item['airline'] ?? null,
                'flight_number' => $item['flight_number'] ?? null,
                'departure_at' => $item['departure_at'] ?? null,
                'return_at' => $item['return_at'] ?? null,
                'duration' => null,
                'expires_at' => $item['expires_at'] ?? null,
                'link' => $item['link'] ?? null,
                'source' => 'v1/calendar',
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $json
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMatrix(array $json, string $from, string $to): array
    {
        $rows = [];

        foreach (($json['data'] ?? []) as $item) {
            if (! is_array($item) || ! isset($item['value'], $item['depart_date'])) {
                continue;
            }

            $date = (string) $item['depart_date'];
            if ($date < $from || $date > $to) {
                continue;
            }

            $rows[] = [
                'date' => $date,
                'price' => (float) $item['value'],
                'origin' => $item['origin'] ?? null,
                'destination' => $item['destination'] ?? null,
                'transfers' => isset($item['number_of_changes']) ? (int) $item['number_of_changes'] : null,
                'airline' => $item['airline'] ?? null,
                'flight_number' => $item['flight_number'] ?? null,
                'departure_at' => $date,
                'return_at' => (isset($item['return_date']) && $item['return_date'] !== '') ? $item['return_date'] : null,
                'duration' => isset($item['duration']) ? (int) $item['duration'] : null,
                'expires_at' => null,
                'found_at' => $item['found_at'] ?? null,
                'actual' => $item['actual'] ?? null,
                'link' => $item['link'] ?? null,
                'source' => 'v2/month-matrix',
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $json
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLatest(array $json, string $from, string $to): array
    {
        $rows = [];

        foreach (($json['data'] ?? []) as $item) {
            if (! is_array($item) || ! isset($item['value'], $item['depart_date'])) {
                continue;
            }

            $date = (string) $item['depart_date'];
            if ($date < $from || $date > $to) {
                continue;
            }

            $rows[] = [
                'date' => $date,
                'price' => (float) $item['value'],
                'origin' => $item['origin'] ?? null,
                'destination' => $item['destination'] ?? null,
                'transfers' => isset($item['number_of_changes']) ? (int) $item['number_of_changes'] : null,
                'airline' => $item['airline'] ?? null,
                'flight_number' => $item['flight_number'] ?? null,
                'departure_at' => $date,
                'return_at' => (isset($item['return_date']) && $item['return_date'] !== '') ? $item['return_date'] : null,
                'duration' => isset($item['duration']) ? (int) $item['duration'] : null,
                'expires_at' => null,
                'found_at' => $item['found_at'] ?? null,
                'actual' => $item['actual'] ?? null,
                'link' => $item['link'] ?? null,
                'source' => 'v2/latest',
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, mixed> $droppedByTripLength
     * @return array<int, array<string, mixed>>
     */
    private function filterRowsByTripMode(array $rows, bool $oneWay, int $length, array &$droppedByTripLength): array
    {
        if ($oneWay) {
            return $rows;
        }

        $filtered = [];
        foreach ($rows as $row) {
            $duration = isset($row['duration']) ? (int) $row['duration'] : null;
            if ($duration !== null && $duration > 0 && abs($duration - $length) > 1) {
                $droppedByTripLength[] = [
                    'date' => $row['date'] ?? null,
                    'source' => $row['source'] ?? null,
                    'duration' => $duration,
                    'expected' => $length,
                ];
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function filterDirectRows(array $rows): array
    {
        return array_values(array_filter($rows, static fn (array $row): bool => (int) ($row['transfers'] ?? 0) === 0));
    }

    /**
     * Забираем все one-way перелёты по одному направлению за нужные месяцы:
     * из v2/month-matrix берём только "чистые" one-way строки (без return_date),
     * из v2/latest — свежие one-way предложения. Это плечо ("туда" либо "обратно"),
     * из которых потом сами собираем пары.
     *
     * @param array<int, string> $months
     * @param array<string, mixed> $config
     * @param array<int, mixed> $requests
     * @return array<int, array<string, mixed>>
     */
    private function fetchOneWayLegRows(string $legOrigin, string $legDestination, array $months, string $from, string $to, array $config, array &$requests): array
    {
        $rows = [];

        foreach ($months as $month) {
            $matrix = $this->tpRequest('/v2/prices/month-matrix', [
                'origin' => $legOrigin,
                'destination' => $legDestination,
                'month' => $month . '-01',
                'currency' => 'RUB',
                'show_to_affiliates' => 'true',
            ], $config);

            $matrixRows = array_values(array_filter(
                $this->normalizeMatrix($matrix, $from, $to),
                static fn (array $row): bool => empty($row['return_at']),
            ));
            $rows = array_merge($rows, $matrixRows);
            $requests[] = ['method' => 'v2/prices/month-matrix', 'purpose' => 'leg', 'route' => $legOrigin . '-' . $legDestination, 'month' => $month, 'ok' => true];

            $page = 1;
            $limit = 1000;
            do {
                $latest = $this->tpRequest('/v2/prices/latest', [
                    'origin' => $legOrigin,
                    'destination' => $legDestination,
                    'currency' => 'RUB',
                    'period_type' => 'month',
                    'beginning_of_period' => $month . '-01',
                    'page' => $page,
                    'limit' => $limit,
                    'show_to_affiliates' => 'true',
                    'sorting' => 'price',
                    'trip_class' => 0,
                    'one_way' => 'true',
                    'trip_duration' => null,
                ], $config);

                $rows = array_merge($rows, $this->normalizeLatest($latest, $from, $to));
                $requests[] = ['method' => 'v2/prices/latest', 'purpose' => 'leg', 'route' => $legOrigin . '-' . $legDestination, 'month' => $month, 'page' => $page, 'ok' => true];

                $count = isset($latest['data']) && is_array($latest['data']) ? count($latest['data']) : 0;
                $page++;
            } while ($count === $limit && $page <= 20);
        }

        return $this->withRouteDefaults($rows, $legOrigin, $legDestination);
    }

    /**
     * Для каждой даты вылета "туда" оставляет одну (самую дешёвую) строку.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, array<string, mixed>>
     */
    private function bestByDateIndexed(array $rows): array
    {
        $best = [];

        foreach ($rows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($date === '' || ! isset($row['price'])) {
                continue;
            }

            if (! isset($best[$date])) {
                $best[$date] = $row;
                continue;
            }

            $price = (float) $row['price'];
            $bestPrice = (float) $best[$date]['price'];

            if ($price < $bestPrice || ($price === $bestPrice && $this->rowRichness($row) > $this->rowRichness($best[$date]))) {
                $best[$date] = $row;
            }
        }

        return $best;
    }

    /**
     * Сами склеиваем самые дешёвые пары "туда-обратно" из отдельно найденных
     * one-way перелётов: для каждой даты вылета перебираем допустимые длительности
     * поездки (желаемая ± 1 день) и берём самую дешёвую подходящую пару.
     *
     * @param array<int, array<string, mixed>> $outRows
     * @param array<int, array<string, mixed>> $backRows
     * @return array<int, array<string, mixed>>
     */
    private function buildRoundTripPairs(array $outRows, array $backRows, int $length, string $from, string $to): array
    {
        $outBest = $this->bestByDateIndexed($outRows);
        $backBest = $this->bestByDateIndexed($backRows);

        $offsets = array_values(array_unique(array_filter(
            [$length - 1, $length, $length + 1],
            static fn (int $value): bool => $value >= 1,
        )));
        if ($offsets === []) {
            $offsets = [max(1, $length)];
        }

        $pairs = [];

        foreach ($outBest as $date => $outRow) {
            if ($date < $from || $date > $to) {
                continue;
            }

            try {
                $departDate = new DateTimeImmutable($date);
            } catch (Throwable) {
                continue;
            }

            $bestPair = null;

            foreach ($offsets as $offset) {
                $returnDate = $departDate->modify('+' . $offset . ' days')->format('Y-m-d');
                if (! isset($backBest[$returnDate])) {
                    continue;
                }

                $backRow = $backBest[$returnDate];
                $total = (float) $outRow['price'] + (float) $backRow['price'];

                if ($bestPair === null || $total < $bestPair['price']) {
                    $bestPair = [
                        'date' => $date,
                        'return_at' => $returnDate,
                        'price' => $total,
                        'origin' => $outRow['origin'] ?? null,
                        'destination' => $outRow['destination'] ?? null,
                        'airline' => $outRow['airline'] ?? null,
                        'flight_number' => $outRow['flight_number'] ?? null,
                        'departure_at' => $outRow['departure_at'] ?? $date,
                        'transfers' => max((int) ($outRow['transfers'] ?? 0), (int) ($backRow['transfers'] ?? 0)),
                        'duration' => null,
                        'expires_at' => null,
                        'found_at' => $outRow['found_at'] ?? $backRow['found_at'] ?? null,
                        'actual' => null,
                        'link' => null,
                        'source' => 'legs-combo',
                        'out_leg' => $outRow,
                        'back_leg' => $backRow,
                    ];
                }
            }

            if ($bestPair !== null) {
                $pairs[] = $bestPair;
            }
        }

        return $pairs;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function bestByDate(array $rows): array
    {
        $best = [];

        foreach ($rows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($date === '') {
                continue;
            }

            if (! isset($best[$date])) {
                $best[$date] = $row;
                continue;
            }

            $rowPrice = $row['price'] ?? INF;
            $bestPrice = $best[$date]['price'] ?? INF;
            if ($rowPrice < $bestPrice) {
                $best[$date] = $row;
                continue;
            }

            if ($rowPrice === $bestPrice && $this->rowRichness($row) > $this->rowRichness($best[$date])) {
                $best[$date] = $row;
            }
        }

        ksort($best);

        return array_values($best);
    }

    private function rowRichness(array $row): int
    {
        $score = 0;

        foreach (['origin', 'destination', 'airline', 'flight_number', 'departure_at', 'return_at', 'link'] as $key) {
            if (! empty($row[$key])) {
                $score++;
            }
        }

        if (isset($row['transfers'])) {
            $score++;
        }

        if (isset($row['duration'])) {
            $score++;
        }

        if (isset($row['found_at'])) {
            $score++;
        }

        return $score;
    }

    /**
     * @param array<int, array<string, mixed>> $selectedRows
     * @param array<int, array<string, mixed>> $sourceRows
     * @return array<int, array<string, mixed>>
     */
    private function enrichRows(array $selectedRows, array $sourceRows): array
    {
        $keys = ['origin', 'destination', 'airline', 'flight_number', 'departure_at', 'return_at', 'transfers', 'duration', 'expires_at', 'found_at', 'actual', 'link'];

        foreach ($selectedRows as &$selected) {
            $date = (string) ($selected['date'] ?? '');
            if ($date === '') {
                continue;
            }

            $candidates = array_values(array_filter($sourceRows, static function (array $row) use ($date, $selected): bool {
                if ((string) ($row['date'] ?? '') !== $date) {
                    return false;
                }

                if (! isset($selected['price']) || ! isset($row['price'])) {
                    return true;
                }

                return abs((float) $row['price'] - (float) $selected['price']) < 0.01;
            }));

            if ($candidates === []) {
                $candidates = array_values(array_filter($sourceRows, static fn (array $row): bool => (string) ($row['date'] ?? '') === $date));
            }

            usort($candidates, fn (array $a, array $b): int => $this->rowRichness($b) <=> $this->rowRichness($a));

            foreach ($candidates as $candidate) {
                foreach ($keys as $key) {
                    if (($selected[$key] ?? null) === null || $selected[$key] === '') {
                        if (isset($candidate[$key]) && $candidate[$key] !== '') {
                            $selected[$key] = $candidate[$key];
                        }
                    }
                }
            }
        }

        unset($selected);

        return $selectedRows;
    }

    private function withRouteDefaults(array $rows, string $origin, string $destination): array
    {
        foreach ($rows as &$row) {
            $row['origin'] = $row['origin'] ?? $origin;
            $row['destination'] = $row['destination'] ?? $destination;
        }

        unset($row);

        return $rows;
    }

    private function hasClockTime(mixed $value): bool
    {
        return is_string($value) && str_contains($value, 'T');
    }

    private function parseTicketLink(?string $link): ?array
    {
        if (! $link || ! preg_match('/[?&]t=[A-Z0-9]{2}(\d{10})(\d{10})(\d{6})([A-Z]{6,}?)(?:(\d{10})(\d{10})(\d{6})([A-Z]{6,}))?_/', $link, $m)) {
            return null;
        }

        $segment = static function (string $dep, string $arr, string $dur, string $chain): array {
            return [
                'dep_ts' => (int) $dep,
                'arr_ts' => (int) $arr,
                'duration' => (int) $dur,
                'chain' => str_split($chain, 3),
            ];
        };

        return [
            'out' => $segment($m[1], $m[2], $m[3], $m[4]),
            'back' => isset($m[5]) && $m[5] !== '' ? $segment($m[5], $m[6], $m[7], $m[8]) : null,
        ];
    }

    private function stopsFromChain(?array $chain): array
    {
        if (! $chain || count($chain) < 3) {
            return [];
        }

        return array_slice($chain, 1, count($chain) - 2);
    }

    private function localIsoFromTs(int $ts, string $airport): string
    {
        $base = gmdate('Y-m-d\TH:i:s', $ts);
        if (isset(self::AIRPORT_UTC_OFFSETS[$airport])) {
            return $base . sprintf('%+03d:00', self::AIRPORT_UTC_OFFSETS[$airport]);
        }

        return $base;
    }

    private function arrivalTimeFromDuration(?string $departureAt, ?int $durationToMinutes, string $destination): ?string
    {
        if (! $departureAt || ! $durationToMinutes || $durationToMinutes <= 0 || ! isset(self::AIRPORT_UTC_OFFSETS[$destination])) {
            return null;
        }

        try {
            $depart = new DateTimeImmutable($departureAt);
        } catch (Throwable) {
            return null;
        }

        $arrivalUtc = $depart->setTimezone(new DateTimeZone('UTC'))->modify('+' . $durationToMinutes . ' minutes');
        $offset = self::AIRPORT_UTC_OFFSETS[$destination];
        $tz = new DateTimeZone(sprintf('%+03d:00', $offset));

        return $arrivalUtc->setTimezone($tz)->format('Y-m-d\TH:i:sP');
    }

    /**
     * Точная one-way деталь по одной "ноге" для показанной топ-даты:
     * реальное время вылета/прилёта, авиакомпания, номер рейса, пересадки.
     * Массовые данные (matrix/latest) отдают только дату без времени,
     * поэтому здесь используется тот же /aviasales/v3/prices_for_dates,
     * что и для one-way поиска, но раздельно для каждого направления.
     *
     * @param array<string, mixed> $config
     * @param array<int, mixed> $requests
     * @return array<string, mixed>|null
     */
    private function fetchLegDetail(string $legOrigin, string $legDest, string $legDate, bool $direct, array $config, array &$requests): ?array
    {
        try {
            $json = $this->tpRequest('/aviasales/v3/prices_for_dates', [
                'origin' => $legOrigin,
                'destination' => $legDest,
                'departure_at' => $legDate,
                'one_way' => 'true',
                'currency' => 'rub',
                'sorting' => 'price',
                'limit' => 10,
                'direct' => $direct ? 'true' : 'false',
            ], $config);
            $requests[] = ['method' => 'aviasales/v3/prices_for_dates', 'purpose' => 'leg-detail', 'route' => $legOrigin . '-' . $legDest, 'date' => $legDate, 'ok' => true];
        } catch (Throwable) {
            $requests[] = ['method' => 'aviasales/v3/prices_for_dates', 'purpose' => 'leg-detail', 'route' => $legOrigin . '-' . $legDest, 'date' => $legDate, 'ok' => false];

            return null;
        }

        $detail = null;
        foreach (($json['data'] ?? []) as $offer) {
            if (! is_array($offer) || ! isset($offer['price'])) {
                continue;
            }
            if (substr((string) ($offer['departure_at'] ?? ''), 0, 10) !== $legDate) {
                continue;
            }
            if ($direct && isset($offer['transfers']) && (int) $offer['transfers'] !== 0) {
                continue;
            }
            if ($detail === null || (float) $offer['price'] < (float) $detail['price']) {
                $detail = $offer;
            }
        }

        if (! $detail) {
            return null;
        }

        $result = [
            'airline' => $detail['airline'] ?? null,
            'flight_number' => $detail['flight_number'] ?? null,
            'found_at' => $detail['found_at'] ?? null,
            'departure_at' => $this->hasClockTime($detail['departure_at'] ?? null) ? $detail['departure_at'] : null,
            'arrival_at' => null,
            'transfers' => isset($detail['transfers']) ? (int) $detail['transfers'] : null,
            'duration' => isset($detail['duration']) ? (int) $detail['duration'] : null,
            'stops' => [],
            'price' => (float) $detail['price'],
        ];

        $ticket = $this->parseTicketLink($detail['link'] ?? null);
        if ($ticket) {
            $result['stops'] = $this->stopsFromChain($ticket['out']['chain']);
            if ($ticket['out']['duration'] > 0) {
                $result['duration'] = $ticket['out']['duration'];
            }
            $result['arrival_at'] = $this->localIsoFromTs($ticket['out']['arr_ts'], $legDest);
        } elseif ($result['departure_at']) {
            $result['arrival_at'] = $this->arrivalTimeFromDuration($result['departure_at'], $result['duration'], $legDest);
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $allRows
     * @return array<int, array<string, mixed>>
     */
    private function enrichBestFlights(
        array $allRows,
        ?array $best,
        string $origin,
        string $destination,
        bool $oneWay,
        int $length,
        bool $direct,
        array $config,
        array &$requests,
    ): array {
        if (! $best) {
            return [];
        }

        $minPrice = $best['price'];
        $bestByDate = [];
        foreach ($allRows as $row) {
            if (($row['price'] ?? null) === $minPrice && ! isset($bestByDate[$row['date']])) {
                $bestByDate[$row['date']] = $row;
            }
        }

        ksort($bestByDate);
        $bestByDate = array_slice($bestByDate, 0, 5, true);

        $legCache = [];
        $legInfo = function (string $legOrigin, string $legDest, string $legDate) use (&$legCache, &$requests, $config): ?array {
            $month = substr($legDate, 0, 7) . '-01';
            $key = $legOrigin . '-' . $legDest . '-' . $month;

            if (! array_key_exists($key, $legCache)) {
                try {
                    $json = $this->tpRequest('/v2/prices/month-matrix', [
                        'origin' => $legOrigin,
                        'destination' => $legDest,
                        'month' => $month,
                        'currency' => 'RUB',
                        'show_to_affiliates' => 'true',
                    ], $config);
                    $requests[] = ['method' => 'v2/prices/month-matrix', 'purpose' => 'leg', 'route' => $legOrigin . '-' . $legDest, 'month' => $month, 'ok' => true];
                    $legCache[$key] = isset($json['data']) && is_array($json['data']) ? $json['data'] : [];
                } catch (Throwable) {
                    $requests[] = ['method' => 'v2/prices/month-matrix', 'purpose' => 'leg', 'route' => $legOrigin . '-' . $legDest, 'month' => $month, 'ok' => false];
                    $legCache[$key] = [];
                }
            }

            foreach ($legCache[$key] as $item) {
                if (! is_array($item) || ($item['depart_date'] ?? null) !== $legDate) {
                    continue;
                }
                if (($item['return_date'] ?? '') !== '') {
                    continue;
                }

                return [
                    'price' => isset($item['value']) ? (float) $item['value'] : null,
                    'transfers' => isset($item['number_of_changes']) ? (int) $item['number_of_changes'] : null,
                    'duration' => isset($item['duration']) ? (int) $item['duration'] : null,
                ];
            }

            return null;
        };

        $flights = [];
        foreach ($bestByDate as $date => $row) {
            $flight = [
                'date' => $date,
                'price' => (float) $row['price'],
                'airline' => $row['airline'] ?? null,
                'flight_number' => $row['flight_number'] ?? null,
                'found_at' => $row['found_at'] ?? null,
                'departure_at' => $this->hasClockTime($row['departure_at'] ?? null) ? $row['departure_at'] : null,
                'return_at' => $oneWay ? null : ($row['return_at'] ?? null),
                'arrival_at' => null,
                'transfers' => $row['transfers'] ?? null,
                'duration' => isset($row['duration']) && (($row['transfers'] ?? null) === 0) ? (int) $row['duration'] : null,
                'stops' => [],
                'detail_price' => null,
                'source' => $row['source'] ?? null,
            ];

            if ($oneWay) {
                try {
                    $json = $this->tpRequest('/aviasales/v3/prices_for_dates', [
                        'origin' => $origin,
                        'destination' => $destination,
                        'departure_at' => $date,
                        'one_way' => 'true',
                        'currency' => 'rub',
                        'sorting' => 'price',
                        'limit' => 10,
                        'direct' => $direct ? 'true' : 'false',
                    ], $config);
                    $requests[] = ['method' => 'aviasales/v3/prices_for_dates', 'date' => $date, 'ok' => true];

                    $detail = null;
                    foreach (($json['data'] ?? []) as $offer) {
                        if (! is_array($offer) || ! isset($offer['price'])) {
                            continue;
                        }
                        if (substr((string) ($offer['departure_at'] ?? ''), 0, 10) !== $date) {
                            continue;
                        }
                        if ($direct && isset($offer['transfers']) && (int) $offer['transfers'] !== 0) {
                            continue;
                        }
                        if ($detail === null || (float) $offer['price'] < (float) $detail['price']) {
                            $detail = $offer;
                        }
                    }

                    if ($detail) {
                        $flight['airline'] = $detail['airline'] ?? $flight['airline'];
                        $flight['flight_number'] = $detail['flight_number'] ?? $flight['flight_number'];
                        if (isset($detail['found_at']) && ! $flight['found_at']) {
                            $flight['found_at'] = $detail['found_at'];
                        }
                        if ($this->hasClockTime($detail['departure_at'] ?? null)) {
                            $flight['departure_at'] = $detail['departure_at'];
                        }
                        if (isset($detail['transfers'])) {
                            $flight['transfers'] = (int) $detail['transfers'];
                        }
                        $flight['detail_price'] = isset($detail['price']) ? (float) $detail['price'] : null;

                        $ticket = $this->parseTicketLink($detail['link'] ?? null);
                        if ($ticket) {
                            $flight['stops'] = $this->stopsFromChain($ticket['out']['chain']);
                            if ($ticket['out']['duration'] > 0) {
                                $flight['duration'] = $ticket['out']['duration'];
                            }
                            $flight['arrival_at'] = $this->localIsoFromTs($ticket['out']['arr_ts'], $destination);
                        } else {
                            $flight['arrival_at'] = $this->arrivalTimeFromDuration(
                                $detail['departure_at'] ?? null,
                                isset($detail['duration']) ? (int) $detail['duration'] : null,
                                $destination,
                            );
                        }
                    }
                } catch (Throwable) {
                    $requests[] = ['method' => 'aviasales/v3/prices_for_dates', 'date' => $date, 'ok' => false];
                }

                $outInfo = $legInfo($origin, $destination, $date);
                if ($outInfo) {
                    $flight['detail_price'] = $flight['detail_price'] ?? $outInfo['price'];
                    if ($flight['transfers'] === null) {
                        $flight['transfers'] = $outInfo['transfers'];
                    }
                    if ($flight['duration'] === null && ($outInfo['transfers'] ?? null) === 0) {
                        $flight['duration'] = $outInfo['duration'];
                    }
                }

                $flights[] = $flight;
                continue;
            }

            // Раунд-трип теперь строится нами самими из отдельных one-way плечей
            // (см. buildRoundTripPairs). Массовые данные (matrix/latest) содержат
            // только дату вылета без точного времени, поэтому для показанных
            // топ-дат отдельно запрашиваем реальные one-way предложения по каждой
            // "ноге" — это даёт настоящее время, авиакомпанию и номер рейса.
            $outLegRow = is_array($row['out_leg'] ?? null) ? $row['out_leg'] : null;
            $backLegRow = is_array($row['back_leg'] ?? null) ? $row['back_leg'] : null;

            $returnDate = null;
            if (! empty($row['return_at'])) {
                $returnDate = substr((string) $row['return_at'], 0, 10);
            } else {
                try {
                    $returnDate = (new DateTimeImmutable($date))->modify('+' . $length . ' days')->format('Y-m-d');
                } catch (Throwable) {
                    $returnDate = null;
                }
            }

            $outDetail = $this->fetchLegDetail($origin, $destination, $date, $direct, $config, $requests);
            $backDetail = $returnDate ? $this->fetchLegDetail($destination, $origin, $returnDate, $direct, $config, $requests) : null;

            $outAirline = $outDetail['airline'] ?? $outLegRow['airline'] ?? null;
            $outFlightNumber = $outDetail['flight_number'] ?? $outLegRow['flight_number'] ?? null;
            $outDeparture = $outDetail['departure_at'] ?? null;
            $outTransfers = $outDetail['transfers'] ?? (isset($outLegRow['transfers']) ? (int) $outLegRow['transfers'] : null);
            $outDuration = $outDetail['duration'] ?? (isset($outLegRow['duration']) ? (int) $outLegRow['duration'] : null);
            $outPrice = $outDetail['price'] ?? $outLegRow['price'] ?? null;
            $outArrival = $outDetail['arrival_at'] ?? ($outDeparture ? $this->arrivalTimeFromDuration($outDeparture, $outDuration, $destination) : null);
            $outStops = $outDetail['stops'] ?? [];

            $flight['airline'] = $flight['airline'] ?? $outAirline;
            $flight['flight_number'] = $flight['flight_number'] ?? $outFlightNumber;
            if (! $flight['found_at']) {
                $flight['found_at'] = $outDetail['found_at'] ?? $outLegRow['found_at'] ?? null;
            }
            if ($outDeparture) {
                $flight['departure_at'] = $outDeparture;
            }
            if ($outTransfers !== null) {
                $flight['transfers'] = $outTransfers;
            }
            if ($outDuration !== null && $outTransfers === 0) {
                $flight['duration'] = $outDuration;
            }
            $flight['arrival_at'] = $outArrival;
            $flight['detail_price'] = $outPrice;
            $flight['stops'] = $outStops;

            $backAirline = $backDetail['airline'] ?? $backLegRow['airline'] ?? null;
            $backFlightNumber = $backDetail['flight_number'] ?? $backLegRow['flight_number'] ?? null;
            $backDeparture = $backDetail['departure_at'] ?? null;
            $backTransfers = $backDetail['transfers'] ?? (isset($backLegRow['transfers']) ? (int) $backLegRow['transfers'] : null);
            $backDuration = $backDetail['duration'] ?? (isset($backLegRow['duration']) ? (int) $backLegRow['duration'] : null);
            $backPrice = $backDetail['price'] ?? $backLegRow['price'] ?? null;
            $backArrival = $backDetail['arrival_at'] ?? ($backDeparture ? $this->arrivalTimeFromDuration($backDeparture, $backDuration, $origin) : null);
            $backStops = $backDetail['stops'] ?? [];

            $flight['return_at'] = $backDeparture ?: $returnDate;

            $outLeg = [
                'origin' => $origin,
                'destination' => $destination,
                'date' => $date,
                'departure_at' => $outDeparture,
                'arrival_at' => $outArrival,
                'airline' => $outAirline,
                'flight_number' => $outFlightNumber,
                'transfers' => $outTransfers,
                'duration' => $outDuration,
                'stops' => $outStops,
                'oneway_price' => $outPrice,
            ];

            $backLeg = [
                'origin' => $destination,
                'destination' => $origin,
                'date' => $returnDate,
                'departure_at' => $backDeparture,
                'arrival_at' => $backArrival,
                'airline' => $backAirline,
                'flight_number' => $backFlightNumber,
                'transfers' => $backTransfers,
                'duration' => $backDuration,
                'stops' => $backStops,
                'oneway_price' => $backPrice,
            ];

            $flight['legs'] = ['out' => $outLeg, 'back' => $backLeg];
            $flights[] = $flight;
        }

        return $flights;
    }

    private function percentile(array $values, float $p): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $index = ($p / 100) * (count($values) - 1);
        $lo = (int) floor($index);
        $hi = (int) ceil($index);

        if ($lo === $hi) {
            return (float) $values[$lo];
        }

        return (float) $values[$lo] + (((float) $values[$hi] - (float) $values[$lo]) * ($index - $lo));
    }

    private function stats(array $rows): array
    {
        $prices = [];

        foreach ($rows as $row) {
            if (isset($row['price']) && (float) $row['price'] > 0) {
                $prices[] = (float) $row['price'];
            }
        }

        if ($prices === []) {
            return [
                'count' => 0,
                'min' => null,
                'max' => null,
                'avg' => null,
                'median' => null,
                'p10' => null,
                'p25' => null,
                'p75' => null,
                'p90' => null,
            ];
        }

        $count = count($prices);

        return [
            'count' => $count,
            'min' => min($prices),
            'max' => max($prices),
            'avg' => array_sum($prices) / $count,
            'median' => $this->percentile($prices, 50),
            'p10' => $this->percentile($prices, 10),
            'p25' => $this->percentile($prices, 25),
            'p75' => $this->percentile($prices, 75),
            'p90' => $this->percentile($prices, 90),
        ];
    }

    private function missingDatesInPeriod(array $rows, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $present = array_fill_keys(array_unique(array_column($rows, 'date')), true);
        $missing = [];

        for ($cursor = $from; $cursor <= $to; $cursor = $cursor->add(new DateInterval('P1D'))) {
            $date = $cursor->format('Y-m-d');
            if (! isset($present[$date])) {
                $missing[] = $date;
            }
        }

        return $missing;
    }

    private function sourceCoverage(array $rows, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $days = count(array_unique(array_column($rows, 'date')));
        $total = $from->diff($to)->days + 1;

        return [
            'covered_days' => $days,
            'total_days' => $total,
            'coverage_percent' => $total ? ($days / $total) * 100 : 0,
        ];
    }

    private function monthCoverage(array $rows, array $months, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $result = [];

        foreach ($months as $month) {
            $monthRows = array_values(array_filter($rows, static fn (array $row): bool => str_starts_with((string) ($row['date'] ?? ''), $month)));
            $windowStart = $from->format('Y-m') === $month ? $from->format('Y-m-d') : $month . '-01';
            $windowEnd = $to->format('Y-m') === $month ? $to->format('Y-m-d') : (new DateTimeImmutable($month . '-01'))->modify('last day of this month')->format('Y-m-d');
            $daysInRange = (new DateTimeImmutable($windowStart))->diff(new DateTimeImmutable($windowEnd))->days + 1;
            $coveredDays = count(array_unique(array_column($monthRows, 'date')));
            $result[] = [
                'month' => $month,
                'days' => $daysInRange,
                'covered_days' => $coveredDays,
                'best_price' => $monthRows ? min(array_column($monthRows, 'price')) : null,
                'coverage_percent' => $daysInRange ? ($coveredDays / $daysInRange) * 100 : 0,
                'window_start' => $windowStart,
                'window_end' => $windowEnd,
                'stats' => $this->stats($monthRows),
            ];
        }

        return $result;
    }

    private function buildBuyPrefix(array $config): ?string
    {
        if (empty($config['partner_id']) || empty($config['tp_trs']) || empty($config['tp_p'])) {
            return null;
        }

        return 'https://tp.media/r?campaign_id=100&marker=' . rawurlencode((string) $config['partner_id'])
            . '&p=' . rawurlencode((string) $config['tp_p'])
            . '&trs=' . rawurlencode((string) $config['tp_trs'])
            . '&u=';
    }

    /**
     * @return array<int, array{from_percent: float, to_percent: float|null, points: float}>
     */
    private function profitabilityRules(): array
    {
        $rules = ProfitabilitySetting::current()->rules ?? [];
        if (! is_array($rules)) {
            return [];
        }

        return array_values(array_filter(array_map(static function (mixed $rule): ?array {
            if (! is_array($rule) || ! isset($rule['from_percent'], $rule['points'])) {
                return null;
            }

            return [
                'from_percent' => (float) $rule['from_percent'],
                'to_percent' => isset($rule['to_percent']) && $rule['to_percent'] !== '' ? (float) $rule['to_percent'] : null,
                'points' => (float) $rule['points'],
            ];
        }, $rules)));
        usort($rules, static function (array $left, array $right): int {
            return $left['from_percent'] <=> $right['from_percent'];
        });

        return $rules;
    }

    /**
     * @param array<int, array{from_percent: float, to_percent: float|null, points: float}> $rules
     */
    private function scoreFromRules(float $savingPercent, array $rules): ?float
    {
        foreach ($rules as $rule) {
            if ($savingPercent < $rule['from_percent']) {
                continue;
            }

            if ($rule['to_percent'] !== null && $savingPercent >= $rule['to_percent']) {
                continue;
            }

            return $rule['points'];
        }

        return null;
    }
}
