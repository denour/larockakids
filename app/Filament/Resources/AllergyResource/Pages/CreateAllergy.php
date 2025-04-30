<?php

namespace App\Filament\Resources\AllergyResource\Pages;

use App\Filament\Resources\AllergyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAllergy extends CreateRecord
{
    protected static string $resource = AllergyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 