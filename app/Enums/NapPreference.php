<?php

namespace App\Enums;

enum NapPreference: string
{
    case Yes = 'yes';
    case No = 'no';
    case Sometimes = 'sometimes';

    public function getLabel(): string
    {
        return __('onboarding.enums.nap.'.$this->value);
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
