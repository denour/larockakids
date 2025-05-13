<?php

namespace App\Filament\Resources\TutorMessageResource\Pages;

use App\Filament\Resources\TutorMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTutorMessage extends EditRecord
{
    protected static string $resource = TutorMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar'),
        ];
    }
} 