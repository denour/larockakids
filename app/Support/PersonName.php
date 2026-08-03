<?php

namespace App\Support;

/**
 * Normaliza nombres de personas a Title Case respetando el español.
 *
 * Un `ucwords()`/`Str::title()` pelado destroza los apellidos compuestos: convierte
 * "Rivera del Toro" en "Rivera Del Toro" y "de la Cruz" en "De La Cruz". Por eso las
 * partículas se mantienen en minúscula.
 *
 * La diferencia entre nombre y apellido importa: en el NOMBRE la primera palabra
 * siempre se capitaliza, pero un APELLIDO puede abrir con partícula ("del Val"), y ahí
 * debe quedarse en minúscula.
 */
final class PersonName
{
    /** Partículas que van en minúscula dentro de un nombre compuesto. */
    private const PARTICLES = ['de', 'del', 'la', 'las', 'los', 'y', 'e', 'da', 'di', 'van', 'von'];

    public static function firstName(?string $value): ?string
    {
        return self::format($value, capitalizeFirst: true);
    }

    public static function lastName(?string $value): ?string
    {
        return self::format($value, capitalizeFirst: false);
    }

    private static function format(?string $value, bool $capitalizeFirst): ?string
    {
        if ($value === null) {
            return null;
        }

        // Colapsa espacios repetidos y recorta: "Rebeca  Martinez" -> "Rebeca Martinez".
        // Una cadena vacía no necesita guarda: explode(' ', '') da [''] y el resto la
        // devuelve intacta. (Lo confirmó el mutation testing: la guarda era código muerto.)
        $clean = preg_replace('/\s+/u', ' ', trim($value));

        $words = explode(' ', mb_strtolower($clean, 'UTF-8'));

        foreach ($words as $i => $word) {
            $keepLower = in_array($word, self::PARTICLES, true) && ! ($capitalizeFirst && $i === 0);

            $words[$i] = $keepLower ? $word : mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
        }

        return implode(' ', $words);
    }
}
