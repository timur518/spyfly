<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Description;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'origin_iata' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', 'exists:airports,iata_code'],
            'destination_iata' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', 'exists:airports,iata_code'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'trip_type' => ['required', 'in:round_trip,one_way'],
            'max_desired_price' => ['nullable', 'numeric', 'min:0'],
            'min_stay_days' => ['nullable', 'integer', 'min:0'],
            'max_stay_days' => ['nullable', 'integer', 'min:0'],
            'channel' => ['sometimes', 'in:email,telegram'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $userId = (int) ($validated['user_id'] ?? $request->user()?->id ?? 0);
        if ($userId <= 0) {
            throw ValidationException::withMessages([
                'user_id' => 'Только авторизованный пользователь может оформить подписку',
            ]);
        }

        $description = Description::create([
            'user_id' => $userId,
            'origin_iata' => strtoupper((string) $validated['origin_iata']),
            'destination_iata' => isset($validated['destination_iata']) && $validated['destination_iata'] !== ''
                ? strtoupper((string) $validated['destination_iata'])
                : null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'trip_type' => $validated['trip_type'],
            'max_desired_price' => $validated['max_desired_price'] ?? null,
            'min_stay_days' => $validated['min_stay_days'] ?? null,
            'max_stay_days' => $validated['max_stay_days'] ?? null,
            'channel' => $validated['channel'] ?? 'email',
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->toPayload($description),
        ], 201);
    }

    private function toPayload(Description $description): array
    {
        return [
            'id' => $description->id,
            'user_id' => $description->user_id,
            'origin_iata' => $description->origin_iata,
            'destination_iata' => $description->destination_iata,
            'date_from' => $description->date_from?->toDateString(),
            'date_to' => $description->date_to?->toDateString(),
            'trip_type' => $description->trip_type,
            'max_desired_price' => $description->max_desired_price,
            'min_stay_days' => $description->min_stay_days,
            'max_stay_days' => $description->max_stay_days,
            'channel' => $description->channel,
            'is_active' => $description->is_active,
            'created_at' => $description->created_at?->toISOString(),
        ];
    }
}
