<?php

namespace App\Enums;

enum GradeLevel: string
{
    case OneYear = '1';
    case TwoYears = '2';
    case ThreeYears = '3';
    case FourYears = '4';

    public function getLabel(): string
    {
        return __('onboarding.enums.grade.'.$this->value);
    }

    /**
     * The final grade before graduating from Piedritas.
     */
    public function isFinal(): bool
    {
        return $this === self::FourYears;
    }

    /**
     * Suggest a grade level based on the kid's age in years.
     */
    public static function fromAge(int $age): self
    {
        return match (true) {
            $age <= 1 => self::OneYear,
            $age === 2 => self::TwoYears,
            $age === 3 => self::ThreeYears,
            default => self::FourYears,
        };
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
