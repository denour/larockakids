<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case WhatsApp = 'whatsapp';
    case Screen = 'screen';

    public function getLabel(): string
    {
        return __('onboarding.enums.notification.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->getLabel()])
            ->all();
    }
}
