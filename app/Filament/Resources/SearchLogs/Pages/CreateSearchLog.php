<?php

namespace App\Filament\Resources\SearchLogs\Pages;

use App\Filament\Resources\SearchLogs\SearchLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSearchLog extends CreateRecord
{
    protected static string $resource = SearchLogResource::class;
}
