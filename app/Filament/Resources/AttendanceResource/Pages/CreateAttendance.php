<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Services\TutorMessageService;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['check_in'] = now();
        $data['check_out'] = null;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $kid = $this->record->kid;
        $contact = $this->record->contact;

        if ($kid && $contact) {
            $tutorMessageService = app(TutorMessageService::class);

            // Verificar si el niño y contacto son nuevos (creados hoy)
            $isNewKid = $kid->created_at->isToday();
            $isNewContact = $contact->created_at->isToday();

            if ($isNewKid && $isNewContact) {
                // Enviar mensaje de bienvenida para nuevos registros
                $tutorMessageService->sendWelcomeMessage($contact, $kid);
            } else {
                // Enviar mensaje de entrada normal
                $tutorMessageService->sendEntryMessage($contact, $kid);
            }

            // Abrir el diálogo de impresión en la misma página
            $this->js("
                const printContent = document.createElement('div');
                printContent.innerHTML = `".view('components.sticker', [
                'kid' => $kid,
                'contact' => $contact,
            ])->render()."`;
                printContent.style.display = 'none';
                document.body.appendChild(printContent);

                const originalContent = document.body.innerHTML;
                document.body.innerHTML = printContent.innerHTML;

                window.print();

                document.body.innerHTML = originalContent;
            ");
        }
    }
}
