<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ServiceTime;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\QrCode;
use Illuminate\Support\Carbon;

class AttendanceScannerService
{
    /**
     * Age (in years) at which a kid must move to Chicos Gigantes.
     */
    private const GIANTS_AGE = 4;

    /**
     * Weeks before reaching the Giants age when a graduation warning is shown.
     */
    private const GRADUATION_WARNING_WEEKS = 4;

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
        $primaryContact = $this->getPrimaryContact($kid);

        if (! $primaryContact) {
            return [
                'success' => false,
                'message' => 'No se encontró un contacto para este niño.',
            ];
        }

        $now = Carbon::now();
        $giantsBirthday = $kid->birth_date->copy()->addYears(self::GIANTS_AGE);
        $requiresGraduation = $now->greaterThanOrEqualTo($giantsBirthday);
        $graduationWarning = $this->graduationAlert($kid, $now, $giantsBirthday);
        $service = ServiceTime::fromHour($now->hour);
        $serviceAttendance = $this->getServiceAttendanceToday($kid, $service);

        if ($serviceAttendance) {
            if ($serviceAttendance->check_in_ip && $serviceAttendance->check_in_ip !== $ip) {
                return [
                    'success' => false,
                    'message' => 'Error de autenticación: esta acción debe realizarse desde el dispositivo original.',
                ];
            }

            $isInside = $serviceAttendance->check_out === null;

            $this->tutorMessageService->sendAssistanceMessage($primaryContact, $kid);

            return [
                'success' => true,
                'message' => $isInside
                    ? 'Ya existe registro para '.$kid->full_name.'. Mensaje de asistencia enviado.'
                    : $kid->full_name.' ya asistió a '.$service->getLabel().'. Mensaje de asistencia enviado.',
                'kid' => $kid,
                'action' => 'assistance',
                'has_active_attendance' => $isInside,
                'warning' => $graduationWarning,
                'requires_graduation' => $requiresGraduation,
            ];
        }

        Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $primaryContact->id,
            'check_in' => $now,
            'check_in_ip' => $ip,
            'status' => AttendanceStatus::EN_CLASE,
            'service' => $service,
        ]);

        $this->tutorMessageService->sendEntryMessage($primaryContact, $kid);

        return [
            'success' => true,
            'message' => 'Entrada registrada para '.$kid->full_name.'.',
            'kid' => $kid,
            'action' => 'check_in',
            'warning' => $graduationWarning,
            'requires_graduation' => $requiresGraduation,
        ];
    }

    /**
     * Process check-out for a QR code.
     *
     * @return array{success: bool, message: string, kid?: Kid, action?: string, no_active_attendance?: bool}
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
     * Process assistance notification for a QR code.
     *
     * @return array{success: bool, message: string, kid?: Kid}
     */
    public function processAssistance(string $code): array
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
        $primaryContact = $this->getPrimaryContact($kid);

        if (! $primaryContact) {
            return [
                'success' => false,
                'message' => 'No se encontró un contacto para este niño.',
            ];
        }

        $this->tutorMessageService->sendAssistanceMessage($primaryContact, $kid);

        return [
            'success' => true,
            'message' => 'Notificación de asistencia enviada a '.$primaryContact->full_name.'.',
            'kid' => $kid,
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
     * Get today's attendance for a kid in a given service, whether the kid is
     * still inside or already checked out. Used to prevent a second attendance
     * in the same service.
     */
    public function getServiceAttendanceToday(Kid $kid, ServiceTime $service): ?Attendance
    {
        return Attendance::where('kid_id', $kid->id)
            ->whereDate('check_in', Carbon::today())
            ->where('service', $service)
            ->first();
    }

    /**
     * Build a graduation alert for a kid: a firm notice once the Chicos
     * Gigantes age is reached, or a heads-up within the warning window before
     * it. Returns null when the kid is still far from the age.
     */
    public function graduationAlert(Kid $kid, Carbon $now, Carbon $giantsBirthday): ?string
    {
        if ($now->greaterThanOrEqualTo($giantsBirthday)) {
            return $kid->full_name.' ya cumplió '.self::GIANTS_AGE.' años y debe pasar a Chicos Gigantes.';
        }

        $warningStart = $giantsBirthday->copy()->subWeeks(self::GRADUATION_WARNING_WEEKS);

        if ($now->lessThan($warningStart)) {
            return null;
        }

        $weeksLeft = max(1, (int) ceil($now->diffInDays($giantsBirthday) / 7));

        return "Faltan {$weeksLeft} semana(s) para que {$kid->full_name} deba pasar a Chicos Gigantes.";
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
