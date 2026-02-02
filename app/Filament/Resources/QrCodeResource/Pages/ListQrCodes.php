<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Filament\Resources\QrCodeResource;
use App\Services\QrCodeService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListQrCodes extends ListRecords
{
    protected static string $resource = QrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateBatch')
                ->label('Generar Lote')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('count')
                        ->label('Cantidad de códigos')
                        ->numeric()
                        ->required()
                        ->default(10)
                        ->minValue(1)
                        ->maxValue(100),
                    Forms\Components\TextInput::make('prefix')
                        ->label('Prefijo')
                        ->default(config('qrcode.prefix', 'LRK'))
                        ->maxLength(10),
                ])
                ->action(function (array $data): void {
                    $service = app(QrCodeService::class);
                    $qrCodes = $service->generateBatch($data['count'], $data['prefix'] ?? null);

                    Notification::make()
                        ->success()
                        ->title('Lote Generado')
                        ->body("Se han generado {$qrCodes->count()} códigos QR exitosamente")
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
