<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'search_log_id',
        'origin_iata',
        'destination_iata',
        'departure_date',
        'return_date',
        'price',
        'baseline_price',
        'deviation_percent',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'search_log_id' => 'integer',
            'departure_date' => 'date',
            'return_date' => 'date',
            'price' => 'decimal:2',
            'baseline_price' => 'decimal:2',
            'deviation_percent' => 'decimal:2',
            'score' => 'integer',
        ];
    }

    public function searchLog(): BelongsTo
    {
        return $this->belongsTo(SearchLog::class);
    }

    protected function routeSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return implode("\n", array_filter([
                $this->airportLabelFor($this->origin_iata),
                $this->airportLabelFor($this->destination_iata),
            ]));
        });
    }

    protected function dateSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return implode("\n", array_filter([
                'Вылет: ' . ($this->departure_date?->format('d.m.Y') ?? '—'),
                'Возврат: ' . ($this->return_date?->format('d.m.Y') ?? '—'),
            ]));
        });
    }

    protected function priceSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return implode("\n", array_filter([
                'Цена: ' . $this->formatMoney($this->price),
                'Средняя: ' . $this->formatMoney($this->baseline_price),
                'Отклонение: ' . $this->formatPercent($this->deviation_percent),
            ]));
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

        return rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.') . '%';
    }
}
