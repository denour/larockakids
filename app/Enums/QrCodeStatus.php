<?php

namespace App\Enums;

enum QrCodeStatus: string
{
    case Available = 'available';
    case Assigned = 'assigned';
    case Lost = 'lost';

    public function getLabel(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Assigned => 'Asignado',
            self::Lost => 'Perdido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Assigned => 'info',
            self::Lost => 'danger',
        };
    }
}
