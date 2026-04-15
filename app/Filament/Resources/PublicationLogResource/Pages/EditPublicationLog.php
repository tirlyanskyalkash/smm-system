<?php

namespace App\Filament\Resources\PublicationLogResource\Pages;

use App\Filament\Resources\PublicationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPublicationLog extends EditRecord
{
    protected static string $resource = PublicationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
