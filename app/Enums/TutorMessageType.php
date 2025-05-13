<?php

namespace App\Enums;

enum TutorMessageType: string
{
    case WELCOME = 'welcome';
    case ENTRY = 'entry';
    case BATHROOM = 'bathroom';
    case DIAPER = 'diaper';
    case UNCONSOLABLE = 'unconsolable';
    case SICK = 'sick';
    case RECOVERED = 'recovered';
    case EXIT = 'exit';

    public function getLabel(): string
    {
        return match($this) {
            self::WELCOME => 'Bienvenida',
            self::ENTRY => 'Entrada',
            self::BATHROOM => 'Baño',
            self::DIAPER => 'Pañal',
            self::UNCONSOLABLE => 'Inconsolable',
            self::SICK => 'Enfermo',
            self::RECOVERED => 'Recuperado',
            self::EXIT => 'Salida',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::WELCOME => 'Se envía cuando se registra un nuevo niño en el sistema.',
            self::ENTRY => 'Se envía cuando se registra la entrada de un niño.',
            self::BATHROOM => 'Se envía cuando un niño necesita ir al baño.',
            self::DIAPER => 'Se envía cuando un niño necesita cambio de pañal.',
            self::UNCONSOLABLE => 'Se envía cuando un niño está inconsolable.',
            self::SICK => 'Se envía cuando un niño se siente enfermo.',
            self::RECOVERED => 'Se envía cuando un niño se ha recuperado.',
            self::EXIT => 'Se envía cuando se registra la salida de un niño.',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(function ($type) {
            return [$type->value => $type->getLabel()];
        })->toArray();
    }

    public static function getTags(): array
    {
        return [
            '[tutor]' => '{tutor_name}',
            '[niño]' => '{kid_name}',
            '[hora]' => '{time}',
            '[fecha]' => '{date}',
            '[alergia]' => '{allergy_name}',
            '[comentario]' => '{comment}',
        ];
    }

    public static function getTagsForSelect(): array
    {
        return collect(self::getTags())->keys()->mapWithKeys(function ($tag) {
            return [$tag => $tag];
        })->toArray();
    }
} 