<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\Country;
use App\Enums\GradeLevel;
use App\Enums\NapPreference;
use App\Enums\NotificationChannel;
use App\Enums\SphincterControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for the two kiosk kid forms (register and edit). Both screens
 * post the exact same payload, so the rules, messages and attribute names live here
 * and the concrete requests only exist to be type-hinted by the controller.
 */
abstract class KidFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Piedritas only takes children up to 4 years old, so the birth date has to sit
     * inside a 5-year window: the day a child turns 5 the date is out of range.
     */
    protected function oldestAllowedBirthDate(): string
    {
        return now()->subYears(5)->toDateString();
    }

    /**
     * @return list<string>
     */
    protected function dialingCodes(): array
    {
        return collect(Country::cases())
            ->map(fn (Country $country): string => $country->getCode())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before_or_equal:today', 'after:'.$this->oldestAllowedBirthDate()],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'grade_level' => ['required', Rule::enum(GradeLevel::class)],
            'classroom' => ['nullable', 'string', 'max:255'],
            'allergy_id' => ['nullable', 'integer', Rule::exists('allergies', 'id')],
            'medical_conditions' => ['nullable', 'string', 'max:255'],
            'medications' => ['nullable', 'string', 'max:255'],
            'sphincter_control' => ['nullable', Rule::enum(SphincterControl::class)],
            'nap' => ['nullable', Rule::enum(NapPreference::class)],
            'routine_notes' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'international_code' => ['nullable', 'string', 'max:5', Rule::in($this->dialingCodes())],
            'phone' => ['required', 'string', 'max:20'],
            'wants_parents_group' => ['boolean'],
            'notification_channel' => ['required', Rule::enum(NotificationChannel::class)],
        ];
    }

    /**
     * Field-specific copy first, then a per-rule fallback for everything else.
     *
     * The app ships no lang/<locale>/validation.php, so a rule that reaches the
     * translator instead of this array renders its raw key ("validation.required")
     * straight into the kiosk. The `fallback_*` entries keyed by bare rule name are
     * Laravel's inline-message escape hatch and close that hole for every field.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('onboarding.validation.name_required'),
            'birth_date.required' => __('onboarding.validation.birth_date_required'),
            'birth_date.before_or_equal' => __('onboarding.validation.birth_date_future'),
            'birth_date.after' => __('onboarding.validation.birth_date_too_old'),
            'gender.required' => __('onboarding.validation.gender_required'),
            'gender.in' => __('onboarding.validation.gender_required'),
            'grade_level.required' => __('onboarding.validation.grade_required'),
            'guardian_name.required' => __('onboarding.validation.guardian_required'),
            'international_code.in' => __('onboarding.validation.country_invalid'),
            'phone.required' => __('onboarding.validation.phone_required'),
            'notification_channel.required' => __('onboarding.validation.notification_required'),

            'required' => __('onboarding.validation.fallback_required'),
            'in' => __('onboarding.validation.fallback_in'),
            'enum' => __('onboarding.validation.fallback_enum'),
            // Laravel looks inline messages up by the bare rule name, so a "max.string"
            // key would never match and the size message would leak its raw key instead.
            'max' => __('onboarding.validation.fallback_max'),
            'date' => __('onboarding.validation.fallback_date'),
            'before_or_equal' => __('onboarding.validation.birth_date_future'),
            'after' => __('onboarding.validation.birth_date_too_old'),
            'integer' => __('onboarding.validation.fallback_integer'),
            'exists' => __('onboarding.validation.fallback_exists'),
            'boolean' => __('onboarding.validation.fallback_boolean'),
            'string' => __('onboarding.validation.fallback_string'),
        ];
    }

    /**
     * Readable field names for the `:attribute` placeholder in the fallbacks.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = __('onboarding.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
