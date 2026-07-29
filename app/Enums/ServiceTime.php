<?php

namespace App\Enums;

enum ServiceTime: string
{
    case First = 'first';
    case Second = 'second';

    public function getLabel(): string
    {
        return match ($this) {
            self::First => '1ra Reunión (11 AM)',
            self::Second => '2da Reunión (1 PM)',
        };
    }

    public function getShortLabel(): string
    {
        return match ($this) {
            self::First => '11 AM',
            self::Second => '1 PM',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::First => 'info',
            self::Second => 'warning',
        };
    }

    /**
     * Determine the service time based on the hour of check-in.
     * Before 1 PM = First service, 1 PM onwards = Second service.
     */
    public static function fromHour(int $hour): self
    {
        return $hour < 13 ? self::First : self::Second;
    }
}
