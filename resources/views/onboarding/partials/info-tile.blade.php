@php
    /**
     * Info tile used on the confirmation screen.
     *
     * Required: $bg (icon bubble background), $c (icon colour), $label.
     * Optional: $icon (o-icon name, defaults to "info"), $value + $sub,
     *           $badge + $badgeColor, $chip + $chipIcon.
     */
    $icon = $icon ?? 'info';
    $chipIcon = $chipIcon ?? 'school';
@endphp
<div class="rounded-2xl border border-slate-100 bg-[#fcfcfe] px-5 py-4 flex flex-wrap items-center gap-x-4 gap-y-3">
    <span class="w-16 h-16 rounded-full shrink-0 flex items-center justify-center" style="background: {{ $bg }}; color: {{ $c }}">
        <x-o-icon :name="$icon" class="w-8 h-8" stroke="1.7" />
    </span>
    <div class="flex-1 min-w-[9rem]">
        <p class="text-sm text-slate-400 leading-tight">{{ $label }}</p>
        @isset($value)
            <p class="mt-0.5 text-lg font-bold text-[#20336b] leading-snug">{{ $value }}</p>
            @if (! empty($sub))
                <p class="text-sm text-slate-400 leading-tight">{{ $sub }}</p>
            @endif
        @endisset
        @isset($badge)
            <span class="mt-1.5 inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-sm font-semibold" style="background: {{ $badgeColor }}1f; color: {{ $badgeColor }}">
                <x-o-icon name="check-badge" class="w-4 h-4 shrink-0" />
                {{ $badge }}
            </span>
        @endisset
    </div>
    @if (! empty($chip))
        <span class="inline-flex items-center gap-1.5 rounded-xl bg-[#eef4ff] px-3 py-2 text-sm font-semibold text-[#2f5bd6] shrink-0">
            <x-o-icon :name="$chipIcon" class="w-4 h-4 shrink-0" stroke="1.8" />
            {{ $chip }}
        </span>
    @endif
</div>
