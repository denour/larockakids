@php
    /**
     * Kiosk language switcher. Built on <details>/<summary> so it works without
     * Alpine or any JS bundle (the onboarding layout only ships CSS).
     *
     * @var array<string, string> $localeLabels
     */
    $localeLabels = [
        'es' => __('onboarding.common.language_es'),
        'en' => __('onboarding.common.language_en'),
    ];
    $current = app()->getLocale();
    $currentLabel = $localeLabels[$current] ?? reset($localeLabels);
@endphp

<details class="group relative">
    <summary
        class="list-none cursor-pointer select-none inline-flex items-center gap-1.5 sm:gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-2 sm:px-4 text-sm text-slate-600 shadow-sm transition hover:border-slate-300 [&::-webkit-details-marker]:hidden"
        aria-label="{{ __('onboarding.common.language') }}">
        <x-o-icon name="globe" class="w-5 h-5 shrink-0 text-slate-400" stroke="1.6" />
        <span class="hidden sm:inline font-medium">{{ $currentLabel }}</span>
        <span class="sm:hidden font-bold uppercase">{{ $current }}</span>
        <x-o-icon name="chevron-down" class="w-4 h-4 shrink-0 text-slate-400 transition group-open:rotate-180" stroke="2" />
    </summary>

    <div class="absolute right-0 z-30 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-lg">
        @foreach ($localeLabels as $locale => $label)
            <a href="{{ route('onboarding.locale', $locale) }}"
                @if ($locale === $current) aria-current="true" @endif
                class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm transition hover:bg-[#f7f9ff] {{ $locale === $current ? 'font-bold text-[#2f5bd6]' : 'text-slate-600' }}">
                {{ $label }}
                @if ($locale === $current)
                    <x-o-icon name="check" class="w-4 h-4 shrink-0" stroke="2.4" />
                @endif
            </a>
        @endforeach
    </div>
</details>
