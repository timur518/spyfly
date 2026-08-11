<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Alert;
use App\Models\SearchLog;
use App\Models\User;
use Filament\Widgets\Widget;

class SystemStatsWidget extends Widget
{
    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.system-stats-widget';

    protected function getViewData(): array
    {
        $averageDiscount = Alert::query()
            ->whereNotNull('deviation_percent')
            ->where('deviation_percent', '<', 0)
            ->avg('deviation_percent');

        $discountValue = $averageDiscount !== null ? abs((float) $averageDiscount) : null;

        return [
            'stats' => [
                [
                    'label' => 'Пользователи',
                    'value' => $this->formatCount(User::query()->count()),
                    'hint' => 'Аккаунты в системе',
                    'unit' => 'всего',
                    'glyph' => '◌',
                    'accent' => 'linear-gradient(90deg, rgba(245, 158, 11, 0.95), rgba(251, 191, 36, 0.35))',
                ],
                [
                    'label' => 'Выполнено поисков',
                    'value' => $this->formatCount(SearchLog::query()->where('status', 'completed')->count()),
                    'hint' => 'Поисковые запросы с завершённым расчётом',
                    'unit' => 'запросов',
                    'glyph' => '↗',
                    'accent' => 'linear-gradient(90deg, rgba(96, 165, 250, 0.95), rgba(56, 189, 248, 0.35))',
                ],
                [
                    'label' => 'Выгодные сигналы',
                    'value' => $this->formatCount(Alert::query()->count()),
                    'hint' => 'Записи, попавшие в ленту сигналов',
                    'unit' => 'сигналов',
                    'glyph' => '✈',
                    'accent' => 'linear-gradient(90deg, rgba(16, 185, 129, 0.95), rgba(52, 211, 153, 0.35))',
                ],
                [
                    'label' => 'Средняя скидка',
                    'value' => $this->formatPercent($discountValue),
                    'hint' => 'По сигналам с рассчитанной скидкой',
                    'unit' => 'среднее',
                    'glyph' => '≈',
                    'accent' => 'linear-gradient(90deg, rgba(248, 113, 113, 0.95), rgba(244, 114, 182, 0.35))',
                ],
            ],
        ];
    }

    private function formatCount(int $count): string
    {
        return number_format($count, 0, '.', ' ');
    }

    private function formatPercent(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . '%';
    }
}
