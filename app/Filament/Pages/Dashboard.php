<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\SystemStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Статистика системы';

    public function getWidgets(): array
    {
        return [
            SystemStatsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
