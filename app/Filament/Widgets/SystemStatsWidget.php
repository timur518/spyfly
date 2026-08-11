<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Alert;
use App\Models\SearchLog;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $averageDiscount = Alert::query()
            ->whereNotNull('deviation_percent')
            ->where('deviation_percent', '<', 0)
            ->avg('deviation_percent');

        $discountValue = $averageDiscount !== null ? abs((float) $averageDiscount) : null;

        return [
            Stat::make('Пользователи', $this->formatCount(User::query()->count()))
                ->description('Аккаунты в системе')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
            Stat::make('Выполнено поисков', $this->formatCount(SearchLog::query()->where('status', 'completed')->count()))
                ->description('Поисковые запросы с завершённым расчётом')
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color('info'),
            Stat::make('Выгодные сигналы', $this->formatCount(Alert::query()->count()))
                ->description('Записи, попавшие в ленту сигналов')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('success'),
            Stat::make('Средняя скидка', $this->formatPercent($discountValue))
                ->description('По сигналам с рассчитанной скидкой')
                ->descriptionIcon('heroicon-m-percent-badge')
                ->color('danger'),
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
