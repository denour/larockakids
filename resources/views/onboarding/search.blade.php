@extends('onboarding.layout')

@section('title', __('onboarding.search.title'))

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

            <div class="w-[84px] h-[84px] rounded-full bg-[#e9eefb] flex items-center justify-center text-[#1c46c9]">
                <x-o-icon name="search" class="w-10 h-10" stroke="2.1" />
            </div>
        </div>
    </div>

    <h1 class="mt-8 text-center text-[32px] sm:text-[36px] leading-tight font-extrabold text-[#20336b]">{{ __('onboarding.search.heading') }}</h1>
    <p class="mt-2.5 text-center text-sm text-[#7c88a5]">{{ __('onboarding.search.lead') }}</p>

    <form method="POST" action="{{ route('onboarding.find') }}" class="mt-8">
        @csrf
        <label for="name" class="mb-2 block text-sm font-bold text-[#20336b]">{{ __('onboarding.search.field_label') }}</label>
        <div class="flex flex-col gap-2 rounded-2xl border-2 p-2 transition focus-within:border-[#7ea4ec] focus-within:ring-4 focus-within:ring-[#2f5bd6]/10 sm:flex-row sm:items-center {{ $errors->has('name') ? 'border-red-300' : 'border-[#d5e2fa]' }}">
            <div class="flex flex-1 items-center gap-3 px-1">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f1f4fb] text-[#8a98b2]">
                    <x-o-icon name="user" class="w-5 h-5" stroke="1.7" />
                </span>
                <input id="name" name="name" value="{{ old('name') }}" placeholder="{{ __('onboarding.search.field_placeholder') }}" autofocus
                    class="w-full bg-transparent py-2.5 text-base text-[#20336b] outline-none placeholder:text-[#98a4bd]">
            </div>
            <button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[#2f5bd6] px-7 py-3 text-base font-bold text-white transition hover:bg-[#2650c2]">
                <x-o-icon name="search" class="w-5 h-5" stroke="2.2" />
                {{ __('onboarding.search.submit') }}
            </button>
        </div>
        @error('name')
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </form>

    @if (session('matches'))
        <div class="mt-4 overflow-hidden rounded-2xl border border-[#dde7fb] bg-[#f5f8fd]">
            <p class="flex items-center gap-2 px-4 py-3 text-sm font-bold text-[#20336b]">
                <x-o-icon name="users-group" class="w-5 h-5 shrink-0 text-[#2f5bd6]" stroke="1.7" />
                {{ __('onboarding.search.matches_title') }}
            </p>
            <div class="divide-y divide-[#e7eefb] border-t border-[#e7eefb] bg-white">
                @foreach (session('matches') as $id => $fullName)
                    <a href="{{ route('onboarding.confirm', $id) }}" class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-[#f5f8fd]">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e9eefb] text-[#2f5bd6]">
                            <x-o-icon name="user" class="w-5 h-5" stroke="1.7" />
                        </span>
                        <span class="min-w-0 flex-1 truncate font-semibold text-[#20336b]">{{ $fullName }}</span>
                        <x-o-icon name="chevron-right" class="w-4 h-4 shrink-0 text-[#a9b7d4]" stroke="2.2" />
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-[#dde9fb] bg-[#f5f9fe] px-4 py-3.5">
            <x-o-icon name="info" class="mt-0.5 w-5 h-5 shrink-0 text-[#2f5bd6]" stroke="1.7" />
            <span class="text-sm text-[#33477e]">{{ __('onboarding.search.hint') }}</span>
        </div>
    @endif

    <div class="relative mt-9 flex items-center justify-center">
        <span aria-hidden="true" class="absolute inset-x-0 h-px bg-slate-200"></span>
        <span class="relative bg-white px-4 text-sm font-bold text-[#20336b]">{{ __('onboarding.search.tips_title') }}</span>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ([
            ['bg' => '#e8f5ed', 'ring' => '#a8d8bd', 'c' => '#14804a', 'glyph' => 'Aa', 'icon' => null, 'n' => 1],
            ['bg' => '#fef6e4', 'ring' => '#f4d38d', 'c' => '#d18f14', 'glyph' => null, 'icon' => 'user', 'n' => 2],
            ['bg' => '#f0edfb', 'ring' => '#c6b4ef', 'c' => '#6b3fce', 'glyph' => '?', 'icon' => null, 'n' => 3],
        ] as $tip)
            <div class="rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[15px] font-extrabold ring-1" style="background: {{ $tip['bg'] }}; color: {{ $tip['c'] }}; --tw-ring-color: {{ $tip['ring'] }}">
                        @if ($tip['icon'])
                            <x-o-icon :name="$tip['icon']" class="w-5 h-5" stroke="1.9" />
                        @else
                            {{ $tip['glyph'] }}
                        @endif
                    </span>
                    <p class="text-sm font-bold leading-tight text-[#20336b]">{{ __('onboarding.search.tip_'.$tip['n'].'_title') }}</p>
                </div>
                <p class="mt-2 text-[13px] leading-relaxed text-[#5e6d92]">{{ __('onboarding.search.tip_'.$tip['n'].'_text') }}</p>
            </div>
        @endforeach
    </div>

    <div class="relative mt-9 flex items-center justify-center">
        <span aria-hidden="true" class="absolute inset-x-0 h-px bg-slate-200"></span>
        <span class="relative flex h-11 w-11 items-center justify-center rounded-full bg-[#f4f2fc] text-[#6d28d9]">
            <x-o-icon name="headset" class="w-6 h-6" stroke="1.7" />
        </span>
    </div>

    <p class="mt-3 text-center text-base font-bold text-[#20336b]">{{ __('onboarding.search.help_title') }}</p>
    <p class="mt-1 text-center text-sm text-[#5e6d92]">{{ __('onboarding.search.help_text') }}</p>

    <div class="mt-7 flex items-center gap-4 rounded-2xl border border-[#eee9fb] bg-[#faf9fe] px-5 py-4">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#eeebfa] text-[#5b31c4]">
            <x-o-icon name="shield-health" class="w-6 h-6" stroke="1.7" />
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-bold text-[#5548c8]">{{ __('onboarding.common.secure') }}</span>
            <span class="mt-0.5 block text-sm text-[#5b678e]">{{ __('onboarding.common.secure_tech') }}</span>
        </span>
    </div>
</div>
@endsection
