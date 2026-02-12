<?php

namespace App\Filament\Resources\ApiTokenResource\Pages;

use App\Filament\Resources\ApiTokenResource;
use App\Models\ApiToken;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateApiToken extends CreateRecord
{
    protected static string $resource = ApiTokenResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $result = ApiToken::generate(
            $data['name'],
            $data['abilities'] ?? ['*'],
            isset($data['expires_at']) ? new \DateTime($data['expires_at']) : null
        );

        // Show the plain token ONCE
        Notification::make()
            ->title('Token Created!')
            ->body("Copy this token now, it won't be shown again:\n\n" . $result['plain_token'])
            ->success()
            ->persistent()
            ->send();

        return $result['token'];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
