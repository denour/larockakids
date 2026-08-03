<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Los tres modos del kiosco. Cada uno comparte layout y solo cambia su color de
 * acento, sus textos y la ruta que procesa el escaneo.
 */
class ScannerMode
{
    public const ICON_ENTRADA = 'M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z';

    public const ICON_SALIDA = 'M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z';

    public const ICON_ASISTENCIA = 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z';

    /**
     * @return array<string, array<string, string>>
     */
    public static function all(): array
    {
        return [
            'check-in' => [
                'key' => 'check-in',
                'title' => 'ENTRADA',
                'subtitle' => 'Escanea el código QR del niño',
                'label' => 'Entrada',
                'accent' => '#16a34a',
                'accent_dark' => '#15803d',
                'icon' => self::ICON_ENTRADA,
                'route' => 'scanner.check-in',
                'process_route' => 'scanner.check-in.process',
            ],
            'check-out' => [
                'key' => 'check-out',
                'title' => 'SALIDA',
                'subtitle' => 'Escanea el código QR del niño',
                'label' => 'Salida',
                'accent' => '#dc2626',
                'accent_dark' => '#b91c1c',
                'icon' => self::ICON_SALIDA,
                'route' => 'scanner.check-out',
                'process_route' => 'scanner.check-out.process',
            ],
            'assistance' => [
                'key' => 'assistance',
                'title' => 'ASISTENCIA',
                'subtitle' => 'Escanea para notificar al contacto',
                'label' => 'Asistencia',
                'accent' => '#ca8a04',
                'accent_dark' => '#a16207',
                'icon' => self::ICON_ASISTENCIA,
                'route' => 'scanner.assistance',
                'process_route' => 'scanner.assistance.process',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function get(string $key): array
    {
        $modes = self::all();

        if (! isset($modes[$key])) {
            throw new InvalidArgumentException("Modo de escáner desconocido: {$key}");
        }

        return $modes[$key];
    }

    /**
     * Los otros dos modos, para los botones de cambio.
     *
     * @return array<int, array<string, string>>
     */
    public static function others(string $key): array
    {
        self::get($key);

        return array_values(array_filter(
            self::all(),
            fn (string $other) => $other !== $key,
            ARRAY_FILTER_USE_KEY
        ));
    }
}
