<?php

namespace App\Filament\Resources\KidResource\Pages;

use App\Filament\Resources\KidResource;
use App\Filament\Widgets\AgeDistributionChart;
use App\Filament\Widgets\GenderDistributionChart;
use App\Imports\KidImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListKids extends ListRecords
{
    protected static string $resource = KidResource::class;

    #[Url]
    public bool $showCharts = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleCharts')
                ->label(fn () => $this->showCharts ? 'Ocultar Gráficas' : 'Ver Gráficas')
                ->icon(fn () => $this->showCharts ? 'heroicon-o-eye-slash' : 'heroicon-o-chart-pie')
                ->color('gray')
                ->action(fn () => $this->showCharts = ! $this->showCharts),
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

    protected function getHeaderWidgets(): array
    {
        if (! $this->showCharts) {
            return [];
        }

        return [
            AgeDistributionChart::class,
            GenderDistributionChart::class,
        ];
    }
}
