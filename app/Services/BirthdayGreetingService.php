<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Kid;
use Illuminate\Support\Carbon;

/**
 * Felicita al tutor cuando el niño llega y su cumpleaños fue en la última semana.
 *
 * Los niños vienen cada domingo, así que el cumpleaños casi nunca cae el mismo día
 * de la entrada: se mira hacia atrás WINDOW_DAYS días para alcanzar a felicitar los
 * que cayeron entre semana. Cada cumpleaños se felicita una sola vez, aunque el niño
 * entre a las dos reuniones o vuelva el domingo siguiente.
 */
class BirthdayGreetingService
{
    public const WINDOW_DAYS = 7;

    public function __construct(protected TutorMessageService $tutorMessageService) {}

    /**
     * Envía la felicitación si toca. Devuelve true solo si se envió.
     */
    public function greetIfDue(Kid $kid, Contact $contact): bool
    {
        $birthday = $this->recentBirthday($kid, Carbon::today());

        if (! $birthday) {
            return false;
        }

        if ($kid->birthday_greeted_on?->isSameDay($birthday)) {
            return false;
        }

        if (! $this->tutorMessageService->sendBirthdayMessage($contact, $kid)) {
            return false;
        }

        $kid->forceFill(['birthday_greeted_on' => $birthday])->save();

        return true;
    }

    /**
     * El cumpleaños más reciente del niño si cayó entre hace WINDOW_DAYS días y hoy.
     */
    public function recentBirthday(Kid $kid, Carbon $today): ?Carbon
    {
        $birthday = $kid->birth_date->copy()->setYear($today->year);

        if ($birthday->greaterThan($today)) {
            $birthday = $kid->birth_date->copy()->setYear($today->year - 1);
        }

        // El día en que nació no es un cumpleaños.
        if ($birthday->lessThanOrEqualTo($kid->birth_date)) {
            return null;
        }

        if ($birthday->lessThan($today->copy()->subDays(self::WINDOW_DAYS))) {
            return null;
        }

        return $birthday;
    }
}
