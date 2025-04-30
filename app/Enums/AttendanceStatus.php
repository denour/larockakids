<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case EN_CLASE = 'en_clase';
    case NOTIFICADO = 'notificado';
    case RETIRADO = 'retirado';

    public function getLabel(): string
    {
        return match($this) {
            self::EN_CLASE => 'En Clase',
            self::NOTIFICADO => 'Notificado',
            self::RETIRADO => 'Retirado',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::EN_CLASE => 'success',
            self::NOTIFICADO => 'warning',
            self::RETIRADO => 'danger',
        };
    }
} 