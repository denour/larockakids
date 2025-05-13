<?php

namespace Database\Seeders;

use App\Enums\TutorMessageType;
use App\Models\TutorMessage;
use Illuminate\Database\Seeder;

class TutorMessageSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            [
                'label' => TutorMessageType::WELCOME->value,
                'name' => TutorMessageType::WELCOME->getLabel(),
                'message' => "Hola [tutor], nos alegra darte la bienvenida a Piedritas. Cuidaremos a tu hijo(a) [nino] con todo el amor, la paciencia y la alegría que Dios nos da.\n\nMateo 19:14 - \"Dejad a los niños venir a mí, y no se lo impidáis; porque de los tales es el Reino de los Cielos.\"",
                'description' => TutorMessageType::WELCOME->getDescription(),
                'is_active' => true,
            ],
            [
                'label' => TutorMessageType::ENTRY->value,
                'name' => TutorMessageType::ENTRY->getLabel(),
                'message' => "Hola [tutor], [nino] ha sido registrado en Piedritas a las [fecha].",
                'description' => TutorMessageType::ENTRY->getDescription(),
                'is_active' => true,
            ],
            [
                'label' => TutorMessageType::BATHROOM->value,
                'name' => TutorMessageType::BATHROOM->getLabel(),
                'message' => "Hola [tutor], [nino] quiere hacer pipí. ¿Podrías apoyarnos viniendo a Piedritas cuando te sea posible?",
                'description' => TutorMessageType::BATHROOM->getDescription(),
                'is_active' => true,
            ],
            [
                'label' => TutorMessageType::DIAPER->value,
                'name' => TutorMessageType::DIAPER->getLabel(),
                'message' => "Hola [tutor], detectamos que [nino] necesita cambio de pañal. Agradeceríamos mucho tu apoyo si pudieras venir pronto.",
                'description' => TutorMessageType::DIAPER->getDescription(),
                'is_active' => true,
            ],
            [
                'label' => TutorMessageType::UNCONSOLABLE->value,
                'name' => TutorMessageType::UNCONSOLABLE->getLabel(),
                'message' => "Hola [tutor], [nino] se encuentra algo inquieto(a) y no lo hemos podido consolar. ¿Crees que puedas venir a verlo(a)?",
                'description' => TutorMessageType::UNCONSOLABLE->getDescription(),
                'is_active' => true,
            ],
            [
                'label' => TutorMessageType::SICK->value,
                'name' => TutorMessageType::SICK->getLabel(),
                'message' => "Hola [tutor], [nino] dice que se siente un poco mal. Por precaución, te pedimos si puedes venir a verlo(a) en cuanto puedas.",
                'description' => TutorMessageType::SICK->getDescription(),
                'is_active' => true,
            ],
            [
                'label' => TutorMessageType::RECOVERED->value,
                'name' => TutorMessageType::RECOVERED->getLabel(),
                'message' => "Hola [tutor], buenas noticias: [nino] ya se encuentra mejor y ha regresado a sus actividades con normalidad.",
                'description' => TutorMessageType::RECOVERED->getDescription(),
                'is_active' => true,
            ],
            [
                'label' => TutorMessageType::EXIT->value,
                'name' => TutorMessageType::EXIT->getLabel(),
                'message' => "Hola [tutor], [nino] ha salido de Piedritas a las [fecha]. ¡Gracias por tu apoyo!",
                'description' => TutorMessageType::EXIT->getDescription(),
                'is_active' => true,
            ],
        ];

        foreach ($messages as $message) {
            TutorMessage::create($message);
        }
    }
} 