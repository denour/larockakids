@extends('onboarding.layout')

@section('title', __('onboarding.graduation.title'))

@php
    /** Wordmark colours, one per letter of "Piedritas". */
    $wordmarkColors = ['#ef4a3c', '#f7941e', '#3bb273', '#2ec4b6', '#4d9de0', '#5b6ef5', '#9b5de0', '#f5b81c', '#e056a0'];
@endphp

@section('content')
<div class="w-full max-w-6xl bg-white rounded-3xl shadow-[0_20px_60px_-20px_rgba(80,80,140,0.25)] px-6 sm:px-10 py-10 sm:py-12">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10 items-center">
        <div class="flex justify-center">
            <img src="{{ asset('images/onboarding/kid-graduation.png') }}"
                alt="{{ __('onboarding.graduation.image_alt') }}"
                class="w-full max-w-md h-auto select-none" />
        </div>

        <div class="text-center">
            <div class="flex justify-center">
                <div class="relative text-[#7a3ff2]">
                    <span class="absolute -left-9 top-2 h-1 w-4 rounded-full bg-[#c4aef5] rotate-[-35deg]"></span>
                    <span class="absolute -left-10 top-6 h-1 w-4 rounded-full bg-[#c4aef5]"></span>
                    <span class="absolute -right-9 top-2 h-1 w-4 rounded-full bg-[#c4aef5] rotate-[35deg]"></span>
                    <span class="absolute -right-10 top-6 h-1 w-4 rounded-full bg-[#c4aef5]"></span>
                    <x-o-icon name="graduation-cap-solid" class="w-14 h-14" />
                </div>
            </div>

            <div class="my-4 flex items-center justify-center gap-3 text-[#f5b81c]">
                <span class="h-px w-20 bg-[#d9c9f5]"></span>
                <x-o-icon name="star" class="w-5 h-5" stroke="1.6" fill="currentColor" />
                <span class="h-px w-20 bg-[#d9c9f5]"></span>
            </div>

            <h1 class="text-3xl sm:text-[2.3rem] font-extrabold text-[#20336b] leading-tight">{{ __('onboarding.graduation.heading_prefix') }}
                @foreach (str_split('Piedritas') as $i => $letter)<span style="color: {{ $wordmarkColors[$i] }}">{{ $letter }}</span>@endforeach{{ __('onboarding.graduation.heading_suffix') }}
            </h1>

            <div class="my-5 flex items-center justify-center gap-3 text-[#f5b81c]">
                <span class="h-px w-20 bg-[#d9c9f5]"></span>
                <x-o-icon name="star" class="w-5 h-5" stroke="1.6" fill="currentColor" />
                <span class="h-px w-20 bg-[#d9c9f5]"></span>
            </div>

            <p class="text-slate-400 leading-relaxed">{{ __('onboarding.graduation.body', ['name' => $kid->first_name]) }}</p>

            <div class="mt-6 flex items-start gap-3 rounded-2xl border border-[#e6dbf7] bg-[#f5f0ff] px-5 py-4 text-left">
                <span class="text-[#7a3ff2] shrink-0 mt-0.5">
                    <x-o-icon name="info" class="w-8 h-8" stroke="1.7" />
                </span>
                <span class="text-sm text-slate-500 leading-relaxed">
                    <span class="block font-bold text-[#7a3ff2]">{{ __('onboarding.graduation.note_strong') }}</span>
                    {{ __('onboarding.graduation.note') }}
                </span>
            </div>
        </div>
    </div>

    <div class="mt-8 flex justify-center">
        <a href="{{ route('onboarding.entry') }}" class="font-display inline-flex w-full max-w-md items-center justify-center gap-2.5 rounded-2xl bg-[#6d28d9] hover:bg-[#5b21b6] text-white text-lg font-bold px-8 py-4 transition">
            <x-o-icon name="heart" class="w-6 h-6 shrink-0" stroke="1.8" />
            {{ __('onboarding.graduation.cta') }}
        </a>
    </div>
</div>
@endsection
