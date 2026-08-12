<?php

namespace App\Models;

use App\Models\Airport;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Description extends Model
{
    protected $fillable = [
        'user_id',
        'origin_iata',
        'destination_iata',
        'date_from',
        'date_to',
        'trip_type',
        'min_stay_days',
        'max_stay_days',
        'max_desired_price',
        'is_active',
        'channel',
        'last_notified_at',
        'matched_flights',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
            'min_stay_days' => 'integer',
            'max_stay_days' => 'integer',
            'max_desired_price' => 'decimal:2',
            'is_active' => 'boolean',
            'last_notified_at' => 'datetime',
            'matched_flights' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function routeSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return implode("\n", array_filter([
                $this->airportLabelFor($this->origin_iata),
                $this->airportLabelFor($this->destination_iata),
                $this->tripTypeLabel($this->trip_type),
            ]));
        });
    }

    protected function priceSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return 'до ' . $this->formatMoney($this->max_desired_price);
        });
    }

    protected function staySummary(): Attribute
    {
        return Attribute::get(function (): string {
            return implode("\n", array_filter([
                'от ' . ($this->min_stay_days ?? '—'),
                'до ' . ($this->max_stay_days ?? '—'),
            ]));
        });
    }

    protected function dateRangeSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return implode("\n", array_filter([
                'с ' . ($this->date_from?->format('d.m.Y') ?? '—'),
                'по ' . ($this->date_to?->format('d.m.Y') ?? '—'),
            ]));
        });
    }

    protected function channelSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return $this->channelLabel($this->channel);
        });
    }

    private function airportLabelFor(?string $iata): string
    {
        if (! $iata) {
            return '—';
        }

        static $cache = [];

        if (array_key_exists($iata, $cache)) {
            return $cache[$iata];
        }

        $airport = Airport::query()
            ->where('iata_code', $iata)
            ->first();

        return $cache[$iata] = $airport
            ? sprintf('%s - %s', $airport->city, $airport->iata_code)
            : $iata;
    }

    private function tripTypeLabel(?string $state): string
    {
        return match ($state) {
            'round_trip' => 'Туда и обратно',
            'one_way' => 'В одну сторону',
            default => $state ?? '—',
        };
    }

    private function channelLabel(?string $state): string
    {
        return match ($state) {
            'email' => 'Email',
            'telegram' => 'Telegram',
            default => $state ?? '—',
        };
    }

    private function formatMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 0, '.', ' ') . ' ₽';
    }
}
