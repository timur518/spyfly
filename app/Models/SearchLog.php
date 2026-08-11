<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $fillable = [
        'user_id',
        'origin_iata',
        'destination_iata',
        'search_type',
        'date_from',
        'date_to',
        'max_price',
        'min_price',
        'median_price',
        'coverage_percent',
        'results_count',
        'request_payload',
        'provider_payload',
        'response_summary',
        'status',
        'error_message',
        'searched_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
            'max_price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'median_price' => 'decimal:2',
            'coverage_percent' => 'decimal:2',
            'results_count' => 'integer',
            'request_payload' => 'array',
            'provider_payload' => 'array',
            'response_summary' => 'array',
            'searched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function originAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'origin_iata', 'iata_code');
    }

    public function destinationAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'destination_iata', 'iata_code');
    }

    protected function routeSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return implode(' · ', array_filter([
                $this->airportLabelFor($this->origin_iata),
                $this->airportLabelFor($this->destination_iata),
                $this->searchTypeLabel($this->search_type),
            ]));
        });
    }

    protected function dateRangeSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return sprintf(
                '%s — %s',
                $this->date_from?->format('d.m.Y') ?? '—',
                $this->date_to?->format('d.m.Y') ?? '—',
            );
        });
    }

    protected function priceSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return implode(' · ', array_filter([
                'Мин ' . $this->formatMoney($this->min_price),
                'Ср ' . $this->formatMoney($this->median_price),
                'Макс ' . $this->formatMoney($this->max_price),
            ]));
        });
    }

    protected function resultsCoverageSummary(): Attribute
    {
        return Attribute::get(function (): string {
            $results = $this->results_count ?? 0;
            $coverage = $this->coverage_percent !== null ? rtrim(rtrim(number_format((float) $this->coverage_percent, 1, '.', ''), '0'), '.') . '%' : '—';

            return $results . ' резул. · ' . $coverage;
        });
    }

    protected function searchStatusSummary(): Attribute
    {
        return Attribute::get(function (): string {
            $date = $this->searched_at?->format('d.m.Y H:i') ?? '—';

            return $date . ' · ' . $this->searchStatusLabel($this->status);
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

    private function searchTypeLabel(?string $state): string
    {
        return match ($state) {
            'round_trip' => 'Туда и обратно',
            'one_way' => 'В одну сторону',
            default => $state ?? '—',
        };
    }

    private function searchStatusLabel(?string $state): string
    {
        return match ($state) {
            'completed' => 'Завершён',
            'pending' => 'В обработке',
            'failed' => 'Ошибка',
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
