<?php

namespace App\Filament\Resources\KidResource\Pages;

use App\Filament\Resources\KidResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKids extends ListRecords
{
    protected static string $resource = KidResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
