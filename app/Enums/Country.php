<?php

namespace App\Enums;

enum Country: string
{
    case MEXICO = 'MX';
    case USA = 'US';
    case CANADA = 'CA';
    case SPAIN = 'ES';
    case ARGENTINA = 'AR';
    case COLOMBIA = 'CO';
    case CHILE = 'CL';
    case PERU = 'PE';
    case VENEZUELA = 'VE';
    case ECUADOR = 'EC';

    public function getCode(): string
    {
        return match($this) {
            self::MEXICO => '+52',
            self::USA => '+1',
            self::CANADA => '+1',
            self::SPAIN => '+34',
            self::ARGENTINA => '+54',
            self::COLOMBIA => '+57',
            self::CHILE => '+56',
            self::PERU => '+51',
            self::VENEZUELA => '+58',
            self::ECUADOR => '+593',
        };
    }

    public function getFlag(): string
    {
        return match($this) {
            self::MEXICO => 'flag-mx',
            self::USA => 'flag-us',
            self::CANADA => 'flag-ca',
            self::SPAIN => 'flag-es',
            self::ARGENTINA => 'flag-ar',
            self::COLOMBIA => 'flag-co',
            self::CHILE => 'flag-cl',
            self::PERU => 'flag-pe',
            self::VENEZUELA => 'flag-ve',
            self::ECUADOR => 'flag-ec',
        };
    }

    public function getName(): string
    {
        return match($this) {
            self::MEXICO => 'México',
            self::USA => 'Estados Unidos',
            self::CANADA => 'Canadá',
            self::SPAIN => 'España',
            self::ARGENTINA => 'Argentina',
            self::COLOMBIA => 'Colombia',
            self::CHILE => 'Chile',
            self::PERU => 'Perú',
            self::VENEZUELA => 'Venezuela',
            self::ECUADOR => 'Ecuador',
        };
    }

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (Country $country) => [
                $country->value => "{$country->getName()} ({$country->getCode()})"
            ])
            ->toArray();
    }

    public static function getDefaultCountry(): string
    {
        return self::MEXICO->value;
    }
} 