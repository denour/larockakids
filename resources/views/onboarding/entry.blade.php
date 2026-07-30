@extends('onboarding.layout')

@section('title', __('onboarding.entry.title'))

@section('header-right')
    @include('onboarding.partials.lang')
@endsection

@section('content')
<div class="w-full max-w-6xl bg-white rounded-3xl shadow-[0_20px_60px_-20px_rgba(80,80,140,0.25)] px-6 sm:px-12 py-10">
    <div class="flex justify-center">
        <div class="relative">
            <span aria-hidden="true" class="pointer-events-none absolute -left-16 top-6 h-2 w-3.5 -rotate-12 rounded-[2px] bg-[#f0d264]"></span>
            <span aria-hidden="true" class="pointer-events-none absolute -left-20 top-16 h-2 w-3.5 rotate-[18deg] rounded-[2px] bg-[#c9a2e0]"></span>
            <span aria-hidden="true" class="pointer-events-none absolute -left-14 top-24 h-2 w-3.5 -rotate-[28deg] rounded-[2px] bg-[#e9a7cf]"></span>
            <span aria-hidden="true" class="pointer-events-none absolute -right-16 top-2 h-2 w-3.5 rotate-[38deg] rounded-[2px] bg-[#f5a45e]"></span>
            <span aria-hidden="true" class="pointer-events-none absolute -right-20 top-10 h-2 w-3.5 -rotate-12 rounded-[2px] bg-[#c9a2e0]"></span>
            <span aria-hidden="true" class="pointer-events-none absolute -right-16 top-[70px] h-2 w-3.5 rotate-[22deg] rounded-[2px] bg-[#f3c98a]"></span>
            <span aria-hidden="true" class="pointer-events-none absolute -right-14 top-24 h-2 w-3.5 -rotate-[18deg] rounded-[2px] bg-[#c8e08a]"></span>

            <div class="w-[84px] h-[84px] rounded-full bg-[#e9eefb] flex items-center justify-center text-[#1b3fb8]">
                <x-o-icon name="whatsapp-outline" class="w-11 h-11" stroke="1.7" />
            </div>
        </div>
    </div>

    <h1 class="mt-8 text-center text-[32px] sm:text-[36px] leading-tight font-extrabold text-[#20336b]">{{ __('onboarding.entry.heading') }}</h1>
    <p class="mt-1.5 text-center text-[17px] font-bold text-[#20336b]">{{ __('onboarding.entry.subheading') }}</p>
    <p class="mt-2.5 text-center text-sm text-[#7c88a5]">{{ __('onboarding.entry.lead') }}</p>

    <div class="mx-auto mt-6 max-w-[34rem] rounded-2xl border-2 border-dashed border-[#cbd9f2] bg-[#f5f8fd] px-6 py-5 text-center">
        <p class="text-[15px] text-[#20336b]">{{ __('onboarding.entry.code_label') }}</p>
        <div class="mt-2.5 flex justify-center gap-4 font-display text-[40px] font-extrabold leading-none text-[#1c46c9] tabular-nums">
            @foreach (str_split($session->code) as $digit)
                <span>{{ $digit }}</span>
            @endforeach
        </div>
        <p class="mt-4 text-[15px] text-[#4d5c81]">{{ __('onboarding.entry.send_to') }}</p>
        <p class="mt-2 flex flex-nowrap items-center justify-center gap-2.5 whitespace-nowrap text-[22px] font-extrabold text-[#16255c] sm:text-[25px]">
            <x-o-icon name="whatsapp" class="w-6 h-6 shrink-0 text-[#0aa757] sm:w-7 sm:h-7" />
            {{ $whatsappNumber }}
        </p>
    </div>

    <div class="mt-8 flex flex-col gap-7 sm:flex-row sm:items-start sm:gap-2">
        @foreach ([
            ['bg' => '#eaf6ef', 'ic' => '#2f9e74', 'badge' => '#34b06f', 'icon' => 'device', 'n' => 1],
            ['bg' => '#e9effc', 'ic' => '#1c4fd0', 'badge' => '#3b82e6', 'icon' => 'chat-bubble', 'n' => 2],
            ['bg' => '#fef7e7', 'ic' => '#d0a12a', 'badge' => '#f4c95d', 'icon' => 'check-circle', 'n' => 3],
        ] as $step)
            <div class="flex-1 min-w-0">
                <div class="flex items-end gap-2">
                    <span class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full" style="background: {{ $step['bg'] }}; color: {{ $step['ic'] }}">
                        <x-o-icon :name="$step['icon']" class="w-[22px] h-[22px]" stroke="1.7" />
                        <span class="absolute -top-1 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold text-white ring-2 ring-white" style="background: {{ $step['badge'] }}">{{ $step['n'] }}</span>
                    </span>
                    <p class="pb-1 text-[13px] font-bold leading-tight text-[#20336b]">{{ __('onboarding.entry.step_'.$step['n'].'_title') }}</p>
                </div>
                <p class="mt-2 text-[13px] leading-relaxed text-[#5e6d92]">{{ __('onboarding.entry.step_'.$step['n'].'_text') }}</p>
            </div>

            @if (! $loop->last)
                <span aria-hidden="true" class="hidden shrink-0 items-center gap-0.5 pt-6 text-slate-300 sm:flex">
                    <span class="w-3 border-t-2 border-dashed border-slate-300"></span>
                    <x-o-icon name="chevron-right" class="w-3 h-3" stroke="2.5" />
                </span>
            @endif
        @endforeach
    </div>

    <div class="relative mt-8 flex items-center justify-center">
        <span aria-hidden="true" class="absolute inset-x-0 h-px bg-slate-200"></span>
        <span class="relative rounded-full border border-slate-200 bg-white px-4 py-1 text-sm font-bold text-[#20336b]">{{ __('onboarding.common.or') }}</span>
    </div>

    <a href="{{ route('onboarding.search') }}" class="mt-6 flex items-center gap-4 rounded-2xl bg-[#f9f7fd] px-5 py-4 transition hover:bg-[#f3effb]">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#efe7f8] text-[#7c3aad]">
            <x-o-icon name="user" class="w-6 h-6" stroke="1.7" />
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-base font-bold text-[#20336b]">{{ __('onboarding.entry.no_whatsapp_title') }}</span>
            <span class="mt-0.5 block text-sm text-[#5e6d92]">{{ __('onboarding.entry.no_whatsapp_text') }}</span>
        </span>
        <x-o-icon name="chevron-right" class="w-5 h-5 shrink-0 text-[#a78bc9]" stroke="2.2" />
    </a>

    @include('onboarding.partials.secure-footer')
</div>
@endsection

@section('scripts')
<script>
    const statusUrl = "{{ route('onboarding.status', $session->code) }}";
    async function poll() {
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            if (res.ok) {
                const data = await res.json();
                if (data.status === 'matched' && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
            }
        } catch (e) { /* keep polling */ }
        setTimeout(poll, 3000);
    }
    setTimeout(poll, 3000);
</script>
@endsection
