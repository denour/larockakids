@php
    $text = $text ?? __('onboarding.common.secure');
@endphp
<div class="mt-8 flex items-center justify-center gap-2 text-slate-400 text-sm">
    <x-o-icon name="lock" class="w-4 h-4" stroke="1.6" />
    <span>{{ $text }}</span>
</div>
