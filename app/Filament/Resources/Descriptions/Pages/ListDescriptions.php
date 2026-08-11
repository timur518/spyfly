<?php

namespace App\Filament\Resources\Descriptions\Pages;

use App\Filament\Resources\Descriptions\DescriptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDescriptions extends ListRecords
{
    protected static string $resource = DescriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
