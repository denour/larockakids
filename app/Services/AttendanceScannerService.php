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
    public function processCheckIn(string $code): array
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
            $this->tutorMessageService->sendAssistanceMessage($primaryContact, $kid);

            return [
                'success' => true,
                'message' => 'Mensaje de asistencia enviado a '.$primaryContact->full_name.'.',
                'kid' => $kid,
                'action' => 'assistance',
            ];
        }

        Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $primaryContact->id,
            'check_in' => Carbon::now(),
            'status' => AttendanceStatus::EN_CLASE,
        ]);

        $this->tutorMessageService->sendEntryMessage($primaryContact, $kid);

        return [
            'success' => true,
            'message' => 'Entrada registrada para '.$kid->full_name.'.',
            'kid' => $kid,
            'action' => 'check_in',
        ];
    }

    /**
     * Process check-out for a QR code.
     *
     * @return array{success: bool, message: string, kid?: Kid, action?: string}
     */
    public function processCheckOut(string $code): array
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
            ];
        }

        $primaryContact = $this->getPrimaryContact($kid);

        $activeAttendance->update([
            'check_out' => Carbon::now(),
            'status' => AttendanceStatus::RETIRADO,
        ]);

        if ($primaryContact) {
            $this->tutorMessageService->sendExitMessage($primaryContact, $kid);
        }

        return [
            'success' => true,
            'message' => 'Salida registrada para '.$kid->full_name.'.',
            'kid' => $kid,
            'action' => 'check_out',
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
