<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function popular(): JsonResponse
    {
        $airports = Airport::query()
            ->where('is_active', true)
            ->where('is_popular_destination', true)
            ->orderBy('city')
            ->orderBy('name')
            ->get()
            ->map(fn (Airport $airport): array => $this->toPayload($airport))
            ->values();

        return response()->json([
            'data' => $airports,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'string', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:5000'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));
        $limit = $validated['limit'] ?? ($term === '' ? 5000 : 20);

        $query = Airport::query()
            ->where('is_active', true)
            ->orderByDesc('is_popular_destination')
            ->orderBy('city')
            ->orderBy('name')
            ->limit($limit);

        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(function ($query) use ($like): void {
                $query->where('city', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('iata_code', 'like', $like)
                    ->orWhere('additional_names', 'like', $like);
            });
        }

        $airports = $query->get()
            ->map(fn (Airport $airport): array => $this->toPayload($airport))
            ->values();

        return response()->json([
            'data' => $airports,
        ]);
    }

    private function toPayload(Airport $airport): array
    {
        return [
            'code' => $airport->iata_code,
            'iata_code' => $airport->iata_code,
            'city' => $airport->city,
            'name' => $airport->name,
            'label' => sprintf('%s — %s (%s)', $airport->city, $airport->name, $airport->iata_code),
            'additional_names' => $airport->additional_names,
            'is_popular_destination' => $airport->is_popular_destination,
        ];
    }
}
