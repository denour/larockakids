<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Events\WhatsAppNotification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Services\TutorMessageService;

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
                // Generar URL de mensaje de bienvenida para nuevos registros
                $whatsappUrl = $tutorMessageService->getWelcomeMessageUrl($contact, $kid);
            } else {
                // Generar URL de mensaje de entrada normal
                $whatsappUrl = $tutorMessageService->getEntryMessageUrl($contact, $kid);
            }

            // Broadcast to WhatsApp listener page via Pusher
            $phoneNumber = str_replace('+', '', $contact->full_phone);
            $message = ''; // Extract message from URL
            if (preg_match('/text=(.+)$/', $whatsappUrl, $matches)) {
                $message = urldecode($matches[1]);
            }
            broadcast(new WhatsAppNotification($message, $phoneNumber));

            // Also try opening directly (fallback)
            $this->js("window.open('{$whatsappUrl}', '_blank');");

            // Abrir el diálogo de impresión en la misma página
            $this->js("
                const printContent = document.createElement('div');
                printContent.innerHTML = `" . view('components.sticker', [
                    'kid' => $kid,
                    'contact' => $contact,
                ])->render() . "`;
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