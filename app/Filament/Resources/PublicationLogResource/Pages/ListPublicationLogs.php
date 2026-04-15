<?php

namespace App\Filament\Resources\PublicationLogResource\Pages;

use App\Filament\Resources\PublicationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPublicationLogs extends ListRecords
{
    protected static string $resource = PublicationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
