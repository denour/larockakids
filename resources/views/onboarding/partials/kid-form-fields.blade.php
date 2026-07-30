@php
    use App\Enums\Country;
    use App\Enums\GradeLevel;
    use App\Enums\NapPreference;
    use App\Enums\NotificationChannel;
    use App\Enums\SphincterControl;

    $kid = $kid ?? null;
    $numbered = $numbered ?? false;
    $contact = $kid?->contacts->first();

    $val = fn (string $key, $default = null) => old($key, $default);
    $sel = fn (string $key, $current) => old($key, $current instanceof \BackedEnum ? $current->value : $current);

    $nameDefault = $kid ? $kid->full_name : $val('name', request('name', ''));
    /**
     * The register/edit mockups show "Por WhatsApp" preselected, which also matches the
     * WhatsApp-first flow of the kiosk. An existing kid always keeps its stored channel.
     */
    $notifDefault = $sel('notification_channel', $kid?->notification_channel ?? NotificationChannel::WhatsApp);
    /** The mockups show the toggle already on for a brand new registration. */
    $groupDefault = old('wants_parents_group', $kid?->wants_parents_group ?? true);

    /**
     * Piedritas only takes children up to 4 years old, so the birth date is clamped
     * to a 5-year window: the day the child turns 5 the date is out of range.
     */
    $maxBirthDate = now()->toDateString();
    $minBirthDate = now()->subYears(5)->addDay()->toDateString();

    $codeDefault = (string) $val('international_code', $contact?->international_code ?? Country::getDefaultCountry()->getCode());
    $selectedCountry = collect(Country::cases())->first(fn (Country $c): bool => $c->getCode() === $codeDefault)
        ?? Country::getDefaultCountry();

    /** Turns an ISO 3166-1 alpha-2 code such as `MX` into its regional-indicator flag. */
    $flag = fn (Country $c): string => mb_chr(0x1F1E6 + ord($c->value[0]) - 65).mb_chr(0x1F1E6 + ord($c->value[1]) - 65);

    $card = 'rounded-2xl border border-[#edeff7] bg-white p-5 sm:p-6 shadow-[0_6px_20px_-14px_rgba(80,80,140,0.45)]';
    $titleCls = 'text-[16px] font-bold text-[#20336b]';
    $badgeCls = 'w-10 h-10 shrink-0 rounded-[14px] flex items-center justify-center';
    $labelCls = 'block text-[13px] font-semibold text-[#2b3a6b] mb-1.5';
    $optionalCls = 'font-normal text-slate-400';
    $inputCls = 'w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-[14px] text-[#20336b] placeholder:text-slate-300 outline-none transition focus:border-[#8b5cf6] focus:ring-2 focus:ring-[#8b5cf6]/15';
    $selectCls = $inputCls.' appearance-none pr-9 cursor-pointer';
    $errorCls = 'mt-1 text-[12px] font-medium text-red-500';
    $chevron = 'pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400';
    $req = '<span class="text-red-400">*</span>';
    $n = 0;
@endphp

@if ($errors->any())
    <div class="mb-4 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
        <x-o-icon name="info" class="mt-0.5 w-5 h-5 shrink-0 text-red-500" stroke="1.8" />
        <ul class="space-y-1 text-[13px] font-medium text-red-600">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    {{-- 1. Personal --}}
    <section class="{{ $card }}">
        <h2 class="flex items-center gap-3 mb-4">
            <span class="{{ $badgeCls }} bg-[#f2e9fd] text-[#8b5cf6]">
                <x-o-icon name="user" class="w-5 h-5" stroke="1.8" />
            </span>
            <span class="{{ $titleCls }}">{{ $numbered ? (++$n).'. ' : '' }}{{ __('onboarding.form.section_personal') }}</span>
        </h2>
        <div class="space-y-3.5">
            <div>
                <label for="kid-name" class="{{ $labelCls }}">{{ __('onboarding.form.name') }} {!! $req !!}</label>
                <div class="relative">
                    <input id="kid-name" name="name" value="{{ $nameDefault }}" placeholder="{{ __('onboarding.form.name_placeholder') }}" class="{{ $inputCls }} {{ $kid ? 'pr-10' : '' }}">
                    @if ($kid)
                        <x-o-icon name="pencil" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8b5cf6]" stroke="1.7" />
                    @endif
                </div>
                @error('name')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="kid-birth-date" class="{{ $labelCls }}">{{ __('onboarding.form.birth_date') }} {!! $req !!}</label>
                    {{-- `min`/`max` drive the native picker, but the browser would show its
                         own English bubble, so the message is swapped for ours in the script. --}}
                    <input id="kid-birth-date" type="date" name="birth_date"
                           value="{{ $val('birth_date', $kid?->birth_date?->format('Y-m-d')) }}"
                           min="{{ $minBirthDate }}" max="{{ $maxBirthDate }}"
                           data-range-message="{{ __('onboarding.validation.birth_date_too_old') }}"
                           class="{{ $inputCls }}">
                    @error('birth_date')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="kid-age" class="{{ $labelCls }}">{{ __('onboarding.form.age') }}</label>
                    <input id="kid-age" type="text" readonly
                           data-age-template="{{ __('onboarding.common.years_old', ['count' => '__N__']) }}"
                           value="{{ $kid ? __('onboarding.common.years_old', ['count' => $kid->age]) : '' }}"
                           placeholder="{{ __('onboarding.form.age_placeholder') }}"
                           class="{{ $inputCls }} cursor-default">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="kid-gender" class="{{ $labelCls }}">{{ __('onboarding.form.gender') }} {!! $req !!}</label>
                    <div class="relative">
                        <select id="kid-gender" name="gender" class="{{ $selectCls }}">
                            <option value="">{{ __('onboarding.common.select') }}</option>
                            <option value="male" @selected($sel('gender', $kid?->gender) === 'male')>{{ __('onboarding.form.gender_male') }}</option>
                            <option value="female" @selected($sel('gender', $kid?->gender) === 'female')>{{ __('onboarding.form.gender_female') }}</option>
                        </select>
                        <x-o-icon name="chevron-down" class="{{ $chevron }}" stroke="2" />
                    </div>
                    @error('gender')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Escolar --}}
    <section class="{{ $card }}">
        <h2 class="flex items-center gap-3 mb-4">
            <span class="{{ $badgeCls }} bg-[#fdf1da] text-[#e9a53a]">
                <x-o-icon name="backpack" class="w-5 h-5" stroke="1.8" />
            </span>
            <span class="{{ $titleCls }}">{{ $numbered ? (++$n).'. ' : '' }}{{ __('onboarding.form.section_school') }}</span>
        </h2>
        <div class="space-y-3.5">
            <div>
                <label for="kid-grade-level" class="{{ $labelCls }}">{{ __('onboarding.form.grade') }} {!! $req !!}</label>
                <div class="relative">
                    <select id="kid-grade-level" name="grade_level" class="{{ $selectCls }}">
                        <option value="">{{ __('onboarding.form.grade_placeholder') }}</option>
                        {{-- PHP turns the enum's numeric string keys into ints, so both sides are cast back. --}}
                        @foreach (GradeLevel::options() as $value => $label)
                            <option value="{{ $value }}" @selected((string) $sel('grade_level', $kid?->grade_level) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-o-icon name="chevron-down" class="{{ $chevron }}" stroke="2" />
                </div>
                @error('grade_level')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-start gap-2.5 rounded-xl bg-[#fdf7e8] px-3.5 py-3 text-[#a97a08]">
                <x-o-icon name="info" class="w-4 h-4 mt-0.5 shrink-0" stroke="1.7" />
                <span class="text-[12px] leading-snug">{{ __('onboarding.form.grade_hint') }}</span>
            </div>
            <div>
                <label for="kid-classroom" class="{{ $labelCls }}">{{ __('onboarding.form.classroom') }}</label>
                <input id="kid-classroom" name="classroom" value="{{ $val('classroom', $kid?->classroom) }}" placeholder="{{ __('onboarding.form.classroom_placeholder') }}" class="{{ $inputCls }}">
            </div>
        </div>
    </section>

    {{-- 3. Salud --}}
    <section class="{{ $card }}">
        <h2 class="flex items-center gap-3 mb-4">
            <span class="{{ $badgeCls }} bg-[#f2e9fd] text-[#8b5cf6]">
                <x-o-icon name="shield-health" class="w-5 h-5" stroke="1.8" />
            </span>
            <span class="{{ $titleCls }}">{{ $numbered ? (++$n).'. ' : '' }}{{ __('onboarding.form.section_health') }}</span>
        </h2>
        <div class="space-y-3.5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="kid-allergy" class="{{ $labelCls }}">{{ __('onboarding.form.allergies') }}</label>
                    <div class="relative">
                        <select id="kid-allergy" name="allergy_id" class="{{ $selectCls }}">
                            <option value="">{{ __('onboarding.common.none_known') }}</option>
                            @foreach ($allergyOptions as $id => $name)
                                <option value="{{ $id }}" @selected((string) $sel('allergy_id', $kid?->allergies->first()?->id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <x-o-icon name="chevron-down" class="{{ $chevron }}" stroke="2" />
                    </div>
                </div>
                <div>
                    <label for="kid-medical-conditions" class="{{ $labelCls }}">{{ __('onboarding.form.medical_conditions') }} <span class="{{ $optionalCls }}">{{ __('onboarding.common.optional') }}</span></label>
                    <input id="kid-medical-conditions" name="medical_conditions" value="{{ $val('medical_conditions', $kid?->medical_conditions) }}" placeholder="{{ __('onboarding.form.medical_conditions_placeholder') }}" class="{{ $inputCls }}">
                </div>
            </div>
            <div>
                <label for="kid-medications" class="{{ $labelCls }}">{{ __('onboarding.form.medications') }} <span class="{{ $optionalCls }}">{{ __('onboarding.common.optional') }}</span></label>
                <input id="kid-medications" name="medications" value="{{ $val('medications', $kid?->medications) }}" placeholder="{{ __('onboarding.form.medications_placeholder') }}" class="{{ $inputCls }}">
            </div>
        </div>
    </section>

    {{-- 4. Hábitos --}}
    <section class="{{ $card }}">
        <h2 class="flex items-center gap-3 mb-4">
            <span class="{{ $badgeCls }} bg-[#e6effb] text-[#4d9de0]">
                <x-o-icon name="toilet" class="w-5 h-5" stroke="1.8" />
            </span>
            <span class="{{ $titleCls }}">{{ $numbered ? (++$n).'. ' : '' }}{{ __('onboarding.form.section_habits') }}</span>
        </h2>
        <div class="space-y-3.5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="kid-sphincter" class="{{ $labelCls }}">{{ __('onboarding.form.sphincter') }}</label>
                    <div class="relative">
                        <select id="kid-sphincter" name="sphincter_control" class="{{ $selectCls }}">
                            <option value="">{{ __('onboarding.common.select') }}</option>
                            @foreach (SphincterControl::options() as $value => $label)
                                <option value="{{ $value }}" @selected((string) $sel('sphincter_control', $kid?->sphincter_control) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-o-icon name="chevron-down" class="{{ $chevron }}" stroke="2" />
                    </div>
                </div>
                <div>
                    <label for="kid-nap" class="{{ $labelCls }}">{{ __('onboarding.form.nap') }}</label>
                    <div class="relative">
                        <select id="kid-nap" name="nap" class="{{ $selectCls }}">
                            <option value="">{{ __('onboarding.common.select') }}</option>
                            @foreach (NapPreference::options() as $value => $label)
                                <option value="{{ $value }}" @selected((string) $sel('nap', $kid?->nap) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-o-icon name="chevron-down" class="{{ $chevron }}" stroke="2" />
                    </div>
                </div>
            </div>
            <div>
                <label for="kid-routine-notes" class="{{ $labelCls }}">{{ __('onboarding.form.routine_notes') }} <span class="{{ $optionalCls }}">{{ __('onboarding.common.optional') }}</span></label>
                <input id="kid-routine-notes" name="routine_notes" value="{{ $val('routine_notes', $kid?->routine_notes) }}" placeholder="{{ __('onboarding.form.routine_notes_placeholder') }}" class="{{ $inputCls }}">
            </div>
        </div>
    </section>

    {{-- 5. Contacto --}}
    <section class="{{ $card }}">
        <h2 class="flex items-center gap-3 mb-4">
            <span class="{{ $badgeCls }} bg-[#e6f7ee] text-[#2fb277]">
                <x-o-icon name="phone" class="w-5 h-5" stroke="1.8" />
            </span>
            <span class="{{ $titleCls }}">{{ $numbered ? (++$n).'. ' : '' }}{{ __('onboarding.form.section_contact') }}</span>
        </h2>
        <div class="space-y-3.5">
            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] gap-3">
                <div>
                    <label for="kid-guardian-name" class="{{ $labelCls }}">{{ __('onboarding.form.guardian_name') }} {!! $req !!}</label>
                    <div class="relative">
                        <input id="kid-guardian-name" name="guardian_name" value="{{ $val('guardian_name', $contact?->full_name) }}" placeholder="{{ __('onboarding.form.guardian_name_placeholder') }}" class="{{ $inputCls }} {{ $kid ? 'pr-10' : '' }}">
                        @if ($kid)
                            <x-o-icon name="pencil" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8b5cf6]" stroke="1.7" />
                        @endif
                    </div>
                    @error('guardian_name')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="kid-phone" class="{{ $labelCls }}">{{ __('onboarding.form.phone') }} {!! $req !!}</label>
                    <div class="flex items-stretch gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1.5 transition focus-within:border-[#8b5cf6] focus-within:ring-2 focus-within:ring-[#8b5cf6]/15">
                        {{-- Real <select>: the flag is only a label, the submitted value is the dialling code. --}}
                        <div class="relative flex shrink-0 items-center rounded-lg border border-slate-200 bg-white pl-2 pr-6">
                            <select id="kid-country" name="international_code" aria-label="{{ __('onboarding.form.country') }}"
                                    class="appearance-none bg-transparent py-1.5 text-[13px] leading-none text-[#20336b] outline-none cursor-pointer">
                                @foreach (Country::cases() as $country)
                                    <option value="{{ $country->getCode() }}" title="{{ $country->getName() }}" @selected($country === $selectedCountry)>{{ $flag($country) }} {{ $country->value }}</option>
                                @endforeach
                            </select>
                            <x-o-icon name="chevron-down" class="pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" stroke="2.4" />
                        </div>
                        <span data-country-code class="flex shrink-0 items-center text-[14px] font-semibold text-[#5b6b9b]">+{{ $selectedCountry->getCode() }}</span>
                        <input id="kid-phone" name="phone" inputmode="tel" value="{{ $val('phone', $contact?->phone) }}" placeholder="{{ __('onboarding.form.phone_placeholder') }}"
                               class="w-full min-w-0 bg-transparent py-1.5 text-[14px] text-[#20336b] placeholder:text-slate-300 outline-none">
                        <span class="flex shrink-0 items-center pr-1 text-slate-300"><x-o-icon name="lock" class="w-4 h-4" stroke="1.7" /></span>
                    </div>
                    @error('phone')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    <p class="mt-1.5 flex items-center gap-1.5 text-[12px] text-slate-400">
                        <x-o-icon name="info" class="w-3.5 h-3.5 shrink-0" stroke="1.7" />
                        {{ __('onboarding.form.phone_hint') }}
                    </p>
                </div>
            </div>
            <div class="flex items-start gap-2.5 rounded-xl bg-[#f2fbf5] px-3.5 py-3 text-[#2f8f5b]">
                <x-o-icon name="lock" class="w-4 h-4 mt-0.5 shrink-0" stroke="1.7" />
                <span class="text-[12px] leading-snug"><span class="font-bold">{{ __('onboarding.common.secure') }}</span> {{ __('onboarding.common.secure_whatsapp') }}</span>
            </div>
        </div>
    </section>

    {{-- 6. Preferencias --}}
    <section class="{{ $card }}">
        <h2 class="flex items-center gap-3 mb-4">
            <span class="{{ $badgeCls }} bg-[#fde9f1] text-[#ec6ba4]">
                <x-o-icon name="bell" class="w-5 h-5" stroke="1.8" />
            </span>
            <span class="{{ $titleCls }}">{{ $numbered ? (++$n).'. ' : '' }}{{ __('onboarding.form.section_preferences') }}</span>
        </h2>
        {{-- The left column keeps a floor wide enough for its title and toggle to stay
             on one line; everything past that goes to the two radio cards so the longest
             label ("Solo por la pantalla del escenario") wraps in two lines, not three. --}}
        <div class="grid grid-cols-1 sm:grid-cols-[minmax(11rem,1fr)_minmax(0,2.15fr)] gap-4">
            <div>
                <p class="text-[14px] font-bold text-[#20336b]">{{ __('onboarding.form.group_title') }}</p>
                <p class="mt-1 text-[12px] leading-snug text-slate-400">{{ __('onboarding.form.group_text') }}</p>
                <label class="mt-3 inline-flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="wants_parents_group" value="0">
                    <input type="checkbox" name="wants_parents_group" value="1" @checked($groupDefault) class="peer sr-only">
                    <span class="w-9 h-5 shrink-0 rounded-full bg-slate-200 peer-checked:bg-[#1f9d55] peer-focus-visible:ring-2 peer-focus-visible:ring-[#8b5cf6]/40 relative transition after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-4 after:h-4 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-4"></span>
                    <span class="whitespace-nowrap text-[12px] font-semibold text-[#20336b]">{{ __('onboarding.form.group_yes') }}</span>
                </label>
            </div>
            <div class="rounded-2xl border border-[#edeff7] p-4">
                <p class="text-[14px] font-bold text-[#20336b]">{{ __('onboarding.form.notifications_title') }}</p>
                <p class="mt-1 text-[12px] leading-snug text-slate-400">{{ __('onboarding.form.notifications_lead') }}</p>
                <p class="mt-1 text-[12px] leading-snug text-slate-400">{{ __('onboarding.form.notifications_text') }}</p>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2.5">
                    @foreach ([
                        ['value' => 'whatsapp', 'icon' => 'whatsapp', 'ring' => 'bg-[#e6f7ee] text-[#2fb277]', 'title' => 'notifications_whatsapp_title', 'text' => 'notifications_whatsapp_text'],
                        ['value' => 'screen', 'icon' => 'device', 'ring' => 'bg-[#f2e9fd] text-[#8b5cf6]', 'title' => 'notifications_screen_title', 'text' => 'notifications_screen_text'],
                    ] as $option)
                        {{-- Icon stacked over the title so the longest label still fits
                             two lines in the narrow half-column. The radio sits on the icon
                             row, so the title below it needs no gutter reserved for it. --}}
                        <label class="relative block rounded-xl border-2 p-3 cursor-pointer transition {{ $notifDefault === $option['value'] ? 'border-[#9b5de0] bg-[#faf6ff]' : 'border-slate-200 hover:border-slate-300' }}">
                            <input type="radio" name="notification_channel" value="{{ $option['value'] }}" @checked($notifDefault === $option['value']) class="absolute right-2.5 top-3 accent-[#9b5de0]">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full {{ $option['ring'] }}">
                                <x-o-icon name="{{ $option['icon'] }}" class="w-4 h-4" stroke="1.8" />
                            </span>
                            <span class="mt-2 block text-[12px] font-bold leading-tight text-[#20336b]">{{ __('onboarding.form.'.$option['title']) }}</span>
                            <span class="mt-1.5 block text-[11px] leading-snug text-slate-400">{{ __('onboarding.form.'.$option['text']) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('notification_channel')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        var code = document.getElementById('kid-country');
        var codeLabel = document.querySelector('[data-country-code]');

        if (code && codeLabel) {
            code.addEventListener('change', function () {
                codeLabel.textContent = '+' + code.value;
            });
        }

        var birth = document.getElementById('kid-birth-date');
        var age = document.getElementById('kid-age');
        var grade = document.getElementById('kid-grade-level');

        if (! birth || ! age) {
            return;
        }

        var template = age.getAttribute('data-age-template') || '__N__';
        var rangeMessage = birth.getAttribute('data-range-message') || '';

        birth.addEventListener('invalid', function () {
            if (rangeMessage && (birth.validity.rangeOverflow || birth.validity.rangeUnderflow)) {
                birth.setCustomValidity(rangeMessage);
            }
        });

        birth.addEventListener('input', function () {
            birth.setCustomValidity('');
        });

        function syncAge() {
            if (! birth.value) {
                age.value = '';

                return;
            }

            var born = new Date(birth.value + 'T00:00:00');

            if (isNaN(born.getTime())) {
                age.value = '';

                return;
            }

            var now = new Date();
            var years = now.getFullYear() - born.getFullYear();
            var months = now.getMonth() - born.getMonth();

            if (months < 0 || (months === 0 && now.getDate() < born.getDate())) {
                years--;
            }

            if (years < 0 || years > 4) {
                age.value = '';

                return;
            }

            age.value = template.replace('__N__', years);

            if (grade && grade.value === '') {
                grade.value = String(Math.min(Math.max(years, 1), 4));
            }
        }

        birth.addEventListener('change', syncAge);
        birth.addEventListener('input', syncAge);
    })();
</script>
