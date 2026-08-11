<?php

namespace App\Filament\Resources\SearchLogs\Pages;

use App\Filament\Resources\SearchLogs\SearchLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSearchLog extends EditRecord
{
    protected static string $resource = SearchLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
