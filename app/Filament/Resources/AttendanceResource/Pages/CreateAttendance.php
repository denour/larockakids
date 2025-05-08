<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Events\WhatsAppNotification;

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
        // Enviar notificación por WhatsApp
        $kid = $this->record->kid;
        $contact = $this->record->contact;
        
        $message = "👋 *Bienvenido a La Roca Kids*\n\n";
        $message .= "Hola {$contact->first_name},\n\n";
        $message .= "Te informamos que {$kid->first_name} ha ingresado al centro.\n\n";
        $message .= "*Datos del niño:*\n";
        $message .= "Nombre: {$kid->first_name} {$kid->last_name}\n";
        $message .= "Edad: {$kid->age} años\n";
        $message .= "Hora de entrada: " . now()->format('H:i') . "\n";
        
        if ($kid->allergies->isNotEmpty()) {
            $message .= "\n⚠️ *Alergias:*\n";
            $message .= $kid->allergies->pluck('name')->join(', ');
        }

        // Limpiar el número de teléfono
        $phoneNumber = preg_replace('/[^0-9]/', '', $contact->phone);

        // Disparar el evento de WhatsApp
        broadcast(new WhatsAppNotification(
            message: $message,
            phoneNumber: $phoneNumber
        ))->toOthers();

        // Mostrar modal del sticker
        $this->dispatch('open-modal', id: 'sticker-modal', data: [
            'kid' => $kid,
            'contact' => $contact,
        ]);
    }
} 