<?php

use App\Support\PersonName;

// =============================================================
// Z — Zero: sin contenido
// =============================================================

describe('Z — Zero', function () {
    it('deja null como null', function () {
        expect(PersonName::firstName(null))->toBeNull()
            ->and(PersonName::lastName(null))->toBeNull();
    });

    it('devuelve cadena vacía cuando no hay nada que capitalizar', function () {
        expect(PersonName::firstName(''))->toBe('')
            ->and(PersonName::lastName(''))->toBe('');
    });

    it('convierte una cadena de puros espacios en cadena vacía', function () {
        expect(PersonName::firstName('   '))->toBe('')
            ->and(PersonName::lastName("\t \n"))->toBe('');
    });
});

// =============================================================
// O — One: una sola palabra
// =============================================================

describe('O — One', function () {
    it('capitaliza una palabra en minúsculas', function () {
        expect(PersonName::firstName('emmanuel'))->toBe('Emmanuel');
    });

    it('baja una palabra que viene en mayúsculas', function () {
        expect(PersonName::firstName('LOGAN'))->toBe('Logan');
    });

    it('respeta una palabra que ya está bien', function () {
        expect(PersonName::firstName('Naomi'))->toBe('Naomi');
    });

    it('capitaliza una partícula suelta cuando es el nombre', function () {
        // Sin más contexto, un nombre que es solo "de" se capitaliza: abre el campo.
        expect(PersonName::firstName('de'))->toBe('De');
    });

    it('deja la partícula en minúscula cuando es un apellido que abre con ella', function () {
        expect(PersonName::lastName('de'))->toBe('de');
    });
});

// =============================================================
// M — Many: varias palabras y varias partículas
// =============================================================

describe('M — Many', function () {
    it('capitaliza cada palabra de un nombre compuesto', function () {
        expect(PersonName::firstName('eliot gael'))->toBe('Eliot Gael');
    });

    it('mantiene en minúscula las partículas intermedias del apellido', function () {
        expect(PersonName::lastName('rivera del toro'))->toBe('Rivera del Toro');
    });

    it('maneja varias partículas seguidas', function () {
        expect(PersonName::lastName('de los angeles perez'))->toBe('de los Angeles Perez');
    });

    it('normaliza un apellido largo en MAYÚSCULAS', function () {
        expect(PersonName::lastName('CLEMENTE VERDUZCO'))->toBe('Clemente Verduzco');
    });

    it('conserva la "y" como partícula entre apellidos', function () {
        expect(PersonName::lastName('ortiz y montes'))->toBe('Ortiz y Montes');
    });
});

// =============================================================
// B — Boundary: los bordes donde la regla cambia
// =============================================================

describe('B — Boundary', function () {
    it('capitaliza la partícula inicial en un NOMBRE pero no en un APELLIDO', function () {
        expect(PersonName::firstName('del valle'))->toBe('Del Valle')
            ->and(PersonName::lastName('del valle'))->toBe('del Valle');
    });

    it('no confunde una palabra que empieza igual que una partícula', function () {
        // "delia" empieza con "del" pero no es partícula.
        expect(PersonName::lastName('delia dela'))->toBe('Delia Dela');
    });

    it('colapsa espacios repetidos en medio', function () {
        expect(PersonName::firstName('Rebeca   Martinez'))->toBe('Rebeca Martinez');
    });

    it('recorta espacios al inicio y al final', function () {
        expect(PersonName::firstName('  lauren  '))->toBe('Lauren');
    });

    it('capitaliza correctamente una inicial de una sola letra', function () {
        expect(PersonName::firstName('j maria'))->toBe('J Maria');
    });
});

// =============================================================
// I — Interface: acentos, guiones y otros alfabetos
// =============================================================

describe('I — Interface', function () {
    it('capitaliza respetando los acentos', function () {
        expect(PersonName::firstName('josé maría'))->toBe('José María');
    });

    it('baja correctamente las mayúsculas acentuadas', function () {
        expect(PersonName::lastName('HERNÁNDEZ ÁLVAREZ'))->toBe('Hernández Álvarez');
    });

    it('capitaliza ambos lados de un nombre con guion', function () {
        expect(PersonName::firstName('ana-maría'))->toBe('Ana-María');
    });

    it('maneja la ñ', function () {
        expect(PersonName::lastName('MUÑOZ PEÑA'))->toBe('Muñoz Peña');
    });
});

// =============================================================
// E — Exception: entradas sucias que igual deben salir usables
// =============================================================

describe('E — Exception', function () {
    it('no rompe con dígitos pegados al nombre', function () {
        // Basura real de la importación; el mutator no adivina, solo capitaliza.
        expect(PersonName::firstName('ranta 12'))->toBe('Ranta 12');
    });

    it('no rompe con un nombre que es solo un número', function () {
        expect(PersonName::firstName('6642329732'))->toBe('6642329732');
    });

    it('no rompe con signos de puntuación', function () {
        expect(PersonName::firstName('ma. del carmen'))->toBe('Ma. del Carmen');
    });

    it('es idempotente: aplicarlo dos veces da lo mismo', function () {
        $once = PersonName::lastName('rivera del toro');

        expect(PersonName::lastName($once))->toBe($once);
    });
});
