<?php

namespace App\Enums;

enum SphincterControl: string
{
    case UsesDiaper = 'uses_diaper';
    case InProcess = 'in_process';
    case DaytimeControl = 'daytime_control';
    case FullControl = 'full_control';

    public function getLabel(): string
    {
        return __('onboarding.enums.sphincter.'.$this->value);
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
