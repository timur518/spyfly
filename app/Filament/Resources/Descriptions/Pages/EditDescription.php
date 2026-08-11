<?php

namespace App\Filament\Resources\Descriptions\Pages;

use App\Filament\Resources\Descriptions\DescriptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDescription extends EditRecord
{
    protected static string $resource = DescriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
