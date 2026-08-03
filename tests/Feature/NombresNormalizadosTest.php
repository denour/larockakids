<?php

use App\Models\Contact;
use App\Models\Kid;

// Capa "I" del ZOMBIE a nivel de integración: el normalizador ya está probado a fondo
// en tests/Unit/PersonNameTest.php; aquí lo que se verifica es que el mutator esté
// realmente conectado en los modelos y se dispare al guardar.

describe('Kid normaliza sus nombres al guardar', function () {
    it('capitaliza un nombre capturado en minúsculas', function () {
        $kid = Kid::factory()->create(['first_name' => 'eliot gael', 'last_name' => 'garcia alvarez']);

        expect($kid->first_name)->toBe('Eliot Gael')
            ->and($kid->last_name)->toBe('Garcia Alvarez');
    });

    it('baja un nombre capturado en MAYÚSCULAS', function () {
        $kid = Kid::factory()->create(['first_name' => 'NAOMI', 'last_name' => 'LOPEZ VELAZQUEZ']);

        expect($kid->full_name)->toBe('Naomi Lopez Velazquez');
    });

    it('respeta las partículas del apellido', function () {
        $kid = Kid::factory()->create(['first_name' => 'ian mateo', 'last_name' => 'rivera del toro']);

        expect($kid->full_name)->toBe('Ian Mateo Rivera del Toro');
    });

    it('colapsa los espacios repetidos', function () {
        $kid = Kid::factory()->create(['first_name' => 'Rebeca   ', 'last_name' => '  Martinez  del  Rio']);

        expect($kid->full_name)->toBe('Rebeca Martinez del Rio');
    });

    it('también normaliza al actualizar, no solo al crear', function () {
        $kid = Kid::factory()->create(['first_name' => 'Ana', 'last_name' => 'Perez']);

        $kid->update(['first_name' => 'ANA PAULA', 'last_name' => 'corrales rodriguez']);

        expect($kid->fresh()->full_name)->toBe('Ana Paula Corrales Rodriguez');
    });
});

describe('Contact normaliza sus nombres al guardar', function () {
    it('capitaliza nombre y apellido', function () {
        $contact = Contact::factory()->create(['first_name' => 'jaqueline', 'last_name' => 'de la paz']);

        expect($contact->full_name)->toBe('Jaqueline de la Paz');
    });

    it('no interfiere con la limpieza del teléfono', function () {
        $contact = Contact::factory()->create([
            'first_name' => 'MARIA',
            'last_name' => 'del val',
            'phone' => '+52 664-232-9732',
            'international_code' => '+52',
        ]);

        expect($contact->full_name)->toBe('Maria del Val')
            ->and($contact->phone)->toBe('6642329732');
    });
});
