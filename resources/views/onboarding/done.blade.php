@extends('onboarding.layout')

@section('title', __('onboarding.done.title'))

@section('content')
<div class="w-full max-w-6xl bg-white rounded-3xl shadow-[0_20px_60px_-20px_rgba(80,80,140,0.25)] px-6 sm:px-10 py-10 sm:py-12">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10 items-center">
        <div class="flex justify-center">
            <img src="{{ asset('images/onboarding/kid-done.png') }}"
                alt="{{ __('onboarding.done.image_alt') }}"
                class="w-full max-w-md h-auto select-none" />
        </div>

        <div class="text-center">
            <div class="flex justify-center">
                <div class="relative">
                    <span class="absolute -left-10 top-1.5 h-1.5 w-6 rounded-full bg-[#f5c518] rotate-[-35deg]"></span>
                    <span class="absolute -left-12 top-9 h-1.5 w-6 rounded-full bg-[#f0507e]"></span>
                    <span class="absolute -left-10 top-[4.2rem] h-1.5 w-6 rounded-full bg-[#8fbcf7] rotate-[35deg]"></span>
                    <span class="absolute -right-10 top-1.5 h-1.5 w-6 rounded-full bg-[#f7a1c4] rotate-[35deg]"></span>
                    <span class="absolute -right-12 top-9 h-1.5 w-6 rounded-full bg-[#b79cf0]"></span>
                    <span class="absolute -right-10 top-[4.2rem] h-1.5 w-6 rounded-full bg-[#8fdcb0] rotate-[-35deg]"></span>
                    <div class="w-[86px] h-[86px] rounded-full bg-[#e2f5e9] flex items-center justify-center text-[#22a45d]">
                        <x-o-icon name="check" class="w-11 h-11" stroke="2.8" />
                    </div>
                </div>
            </div>

            <h1 class="mt-7 text-4xl font-extrabold text-[#20336b] leading-tight">{{ __('onboarding.done.heading') }}</h1>
            <p class="mt-1.5 text-2xl font-bold text-[#20336b] leading-tight text-balance">
                {{ __('onboarding.done.subheading') }} <span class="text-[#f0507e]">&hearts;</span>
            </p>

            <div class="my-5 flex items-center justify-center gap-3 text-[#9b5de0]">
                <span class="h-px w-20 bg-[#d9c9f5]"></span>
                <x-o-icon name="star" class="w-5 h-5" stroke="1.6" fill="currentColor" />
                <span class="h-px w-20 bg-[#d9c9f5]"></span>
            </div>

            <p class="text-slate-400 leading-relaxed">{{ __('onboarding.done.body', ['name' => $kid->first_name]) }}</p>

            <div class="mt-6 flex items-start gap-3 rounded-2xl bg-[#f2fbf5] px-5 py-4 text-left">
                <span class="w-11 h-11 rounded-full bg-[#e2f5e9] flex items-center justify-center text-[#22a45d] shrink-0">
                    <x-o-icon name="heart" class="w-6 h-6" stroke="1.7" />
                </span>
                <span class="text-sm text-slate-500 leading-relaxed">{{ __('onboarding.done.note') }}</span>
            </div>

            <a href="{{ route('onboarding.entry') }}" class="font-display mt-7 inline-flex items-center justify-center gap-2.5 rounded-2xl bg-[#0f8a3d] hover:bg-[#0c7433] text-white text-lg font-bold px-8 py-3.5 transition">
                <x-o-icon name="user-plus" class="w-6 h-6 shrink-0" stroke="1.8" />
                {{ __('onboarding.done.cta') }}
            </a>
        </div>
    </div>
</div>
@endsection
