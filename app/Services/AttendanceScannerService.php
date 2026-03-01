<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\QrCode;
use Illuminate\Support\Carbon;

class AttendanceScannerService
{
    public function __construct(protected TutorMessageService $tutorMessageService) {}

    /**
     * Process check-in for a QR code.
     *
     * @return array{success: bool, message: string, kid?: Kid, action?: string}
     */
    public function processCheckIn(string $code, ?string $ip = null): array
    {
        $qrCode = QrCode::where('code', $code)->first();

        if (! $qrCode) {
            return [
                'success' => false,
                'message' => 'Código QR no encontrado.',
            ];
        }

        if (! $qrCode->isAssigned() || ! $qrCode->kid_id) {
            return [
                'success' => false,
                'message' => 'Este código QR no está asignado a ningún niño.',
            ];
        }

        $kid = $qrCode->kid;
        $activeAttendance = $this->getActiveAttendanceToday($kid);
        $primaryContact = $this->getPrimaryContact($kid);

        if (! $primaryContact) {
            return [
                'success' => false,
                'message' => 'No se encontró un contacto para este niño.',
            ];
        }

        if ($activeAttendance) {
            if ($activeAttendance->check_in_ip && $activeAttendance->check_in_ip !== $ip) {
                return [
                    'success' => false,
                    'message' => 'Error de autenticación: esta acción debe realizarse desde el dispositivo original.',
                ];
            }

            $whatsappUrl = $this->tutorMessageService->generateWhatsAppUrl('assistance', $primaryContact, $kid);

            return [
                'success' => true,
                'message' => 'Ya existe registro para '.$kid->full_name.'. Enviando mensaje de asistencia.',
                'kid' => $kid,
                'action' => 'assistance',
                'whatsapp_url' => $whatsappUrl,
                'has_active_attendance' => true,
            ];
        }

        Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $primaryContact->id,
            'check_in' => Carbon::now(),
            'check_in_ip' => $ip,
            'status' => AttendanceStatus::EN_CLASE,
        ]);

        $whatsappUrl = $this->tutorMessageService->getEntryMessageUrl($primaryContact, $kid);

        return [
            'success' => true,
            'message' => 'Entrada registrada para '.$kid->full_name.'.',
            'kid' => $kid,
            'action' => 'check_in',
            'whatsapp_url' => $whatsappUrl,
        ];
    }

    /**
     * Process check-out for a QR code.
     *
     * @return array{success: bool, message: string, kid?: Kid, action?: string, whatsapp_url?: string, no_active_attendance?: bool}
     */
    public function processCheckOut(string $code, ?string $ip = null): array
    {
        $qrCode = QrCode::where('code', $code)->first();

        if (! $qrCode) {
            return [
                'success' => false,
                'message' => 'Código QR no encontrado.',
            ];
        }

        if (! $qrCode->isAssigned() || ! $qrCode->kid_id) {
            return [
                'success' => false,
                'message' => 'Este código QR no está asignado a ningún niño.',
            ];
        }

        $kid = $qrCode->kid;
        $activeAttendance = $this->getActiveAttendanceToday($kid);

        if (! $activeAttendance) {
            return [
                'success' => false,
                'message' => 'No hay entrada registrada para '.$kid->full_name.' hoy.',
                'kid' => $kid,
                'no_active_attendance' => true,
            ];
        }

        if ($activeAttendance->check_in_ip && $activeAttendance->check_in_ip !== $ip) {
            return [
                'success' => false,
                'message' => 'Error de autenticación: esta acción debe realizarse desde el dispositivo original.',
            ];
        }

        $primaryContact = $this->getPrimaryContact($kid);

        $activeAttendance->update([
            'check_out' => Carbon::now(),
            'status' => AttendanceStatus::RETIRADO,
        ]);

        $whatsappUrl = null;
        if ($primaryContact) {
            $whatsappUrl = $this->tutorMessageService->getExitMessageUrl($primaryContact, $kid);
        }

        return [
            'success' => true,
            'message' => 'Salida registrada para '.$kid->full_name.'.',
            'kid' => $kid,
            'action' => 'check_out',
            'whatsapp_url' => $whatsappUrl,
        ];
    }

    /**
     * Get active attendance (no check_out) for today.
     */
    public function getActiveAttendanceToday(Kid $kid): ?Attendance
    {
        return Attendance::where('kid_id', $kid->id)
            ->whereDate('check_in', Carbon::today())
            ->whereNull('check_out')
            ->first();
    }

    /**
     * Get primary contact for a kid based on relationship priority.
     * Priority: parent > guardian > family > other
     */
    public function getPrimaryContact(Kid $kid): ?Contact
    {
        $relationshipPriority = ['parent', 'guardian', 'family', 'friend of parent', 'other'];

        $contacts = $kid->contacts()->get();

        foreach ($relationshipPriority as $relationship) {
            $contact = $contacts->first(function ($contact) use ($relationship) {
                return $contact->pivot->relationship_type === $relationship;
            });

            if ($contact) {
                return $contact;
            }
        }

        return $contacts->first();
    }
}
