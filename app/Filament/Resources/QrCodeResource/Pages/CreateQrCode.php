<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Filament\Resources\QrCodeResource;
use App\Services\QrCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateQrCode extends CreateRecord
{
    protected static string $resource = QrCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = app(QrCodeService::class);
        $data['qr_image_path'] = $service->generateQrImage($data['code']);

        return $data;
    }
}
