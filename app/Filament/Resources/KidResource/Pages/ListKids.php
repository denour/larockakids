<?php

namespace App\Filament\Resources\KidResource\Pages;

use App\Filament\Resources\KidResource;
use App\Imports\KidImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListKids extends ListRecords
{
    protected static string $resource = KidResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->modalHeading('Importar desde Excel')
                ->modalDescription('Importa datos a la base de datos desde un archivo Excel')
                ->modalSubmitActionLabel('Importar')
                ->modalCancelActionLabel('Cancelar')
                ->color('primary')
                ->uploadField(fn ($upload) => $upload->label('Archivo Excel'))
                ->use(KidImport::class),
            Actions\CreateAction::make(),
            Action::make('export-kids')
                ->label('Exportar Niños')
                ->url(route('export.kids'))
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->openUrlInNewTab(),
        ];
    }
}
