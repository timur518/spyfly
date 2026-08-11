<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Airport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = max(1, min(20, (int) $request->integer('limit', 8)));

        $signals = Alert::query()
            ->with(['searchLog.originAirport', 'searchLog.destinationAirport'])
            ->whereNotNull('deviation_percent')
            ->where('deviation_percent', '<=', -40)
            ->whereHas('searchLog', static function ($query): void {
                $query->where('search_type', 'one_way');
            })
            ->latest('created_at')
            ->limit(min(100, $limit * 10))
            ->get()
            ->unique(static fn (Alert $alert): string => $alert->origin_iata . '|' . $alert->destination_iata)
            ->take($limit)
            ->values()
            ->map(fn (Alert $alert): array => $this->toPayload($alert))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $signals,
        ]);
    }

    private function toPayload(Alert $alert): array
    {
        $originCity = $this->airportCity(
            $alert->searchLog?->originAirport?->city,
            $alert->origin_iata,
        );
        $destinationCity = $this->airportCity(
            $alert->searchLog?->destinationAirport?->city,
            $alert->destination_iata,
        );

        return [
            'id' => $alert->id,
            'origin_iata' => $alert->origin_iata,
            'destination_iata' => $alert->destination_iata,
            'origin_city' => $originCity,
            'destination_city' => $destinationCity,
            'route_label' => sprintf('%s - %s', $originCity, $destinationCity),
            'price' => (float) $alert->price,
            'price_label' => $this->formatMoney($alert->price),
            'deviation_percent' => $alert->deviation_percent !== null ? (float) $alert->deviation_percent : null,
            'deviation_label' => $this->formatPercent($alert->deviation_percent),
            'departure_date' => $alert->departure_date?->toDateString(),
            'return_date' => $alert->return_date?->toDateString(),
            'score' => $alert->score,
            'created_at' => $alert->created_at?->toISOString(),
        ];
    }

    private function airportCity(?string $relationCity, ?string $iata): string
    {
        if ($relationCity) {
            return $relationCity;
        }

        if (! $iata) {
            return '—';
        }

        return Airport::query()
            ->where('iata_code', $iata)
            ->value('city') ?? $iata;
    }

    private function formatMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 0, '.', ' ') . ' ₽';
    }

    private function formatPercent(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $number = (float) $value;
        $prefix = $number > 0 ? '+' : '';

        return $prefix . rtrim(rtrim(number_format($number, 1, '.', ''), '0'), '.') . '%';
    }
}
