@extends('onboarding.layout')

@section('title', __('onboarding.confirm.title'))

@php
    use App\Enums\NotificationChannel;

    $allergyNames = $kid->allergies->pluck('name')->implode(', ');
    $channel = $kid->notification_channel ?? NotificationChannel::Screen;
    $channelIsWhatsApp = $channel === NotificationChannel::WhatsApp;
@endphp

@section('content')
<div class="w-full max-w-6xl bg-white rounded-3xl shadow-[0_20px_60px_-20px_rgba(80,80,140,0.25)] px-6 sm:px-10 py-9">
    <div class="flex justify-center">
        <div class="relative">
            <span class="absolute -left-9 top-1 h-1.5 w-5 rounded-full bg-[#f5c518] rotate-[-35deg]"></span>
            <span class="absolute -left-11 top-8 h-1.5 w-5 rounded-full bg-[#f0507e]"></span>
            <span class="absolute -left-9 top-[3.6rem] h-1.5 w-5 rounded-full bg-[#8fbcf7] rotate-[35deg]"></span>
            <span class="absolute -right-9 top-1 h-1.5 w-5 rounded-full bg-[#f7a1c4] rotate-[35deg]"></span>
            <span class="absolute -right-11 top-8 h-1.5 w-5 rounded-full bg-[#b79cf0]"></span>
            <span class="absolute -right-9 top-[3.6rem] h-1.5 w-5 rounded-full bg-[#8fdcb0] rotate-[-35deg]"></span>
            <div class="w-[72px] h-[72px] rounded-full bg-[#e2f5e9] flex items-center justify-center text-[#22a45d]">
                <x-o-icon name="check" class="w-9 h-9" stroke="2.6" />
            </div>
        </div>
    </div>
    <h1 class="mt-7 text-center text-3xl sm:text-4xl font-extrabold text-[#20336b]">{{ __('onboarding.confirm.heading') }}</h1>
    <p class="mt-2 text-center text-slate-400">{{ __('onboarding.confirm.lead') }}</p>

    <div class="mt-7 flex items-center gap-4 rounded-2xl border border-slate-100 bg-[#fcfcfe] px-5 py-4">
        <span class="w-16 h-16 rounded-full bg-[#efe6fb] flex items-center justify-center text-[#9b5de0] shrink-0">
            <x-o-icon name="user" class="w-8 h-8" stroke="1.7" />
        </span>
        <div class="min-w-0">
            <p class="text-sm text-slate-400 leading-tight">{{ __('onboarding.confirm.full_name') }}</p>
            <p class="mt-0.5 text-xl sm:text-2xl font-bold text-[#20336b] leading-snug">{{ $kid->full_name }}</p>
        </div>
    </div>

    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
        @include('onboarding.partials.info-tile', [
            'bg' => '#e2f5e9', 'c' => '#22a45d', 'icon' => 'calendar-check',
            'label' => __('onboarding.confirm.age'),
            'value' => __('onboarding.common.years_old', ['count' => $kid->age]),
            'sub' => '('.$kid->birth_date->format('d/m/Y').')',
        ])
        @include('onboarding.partials.info-tile', [
            'bg' => '#fdf1d5', 'c' => '#e8a908', 'icon' => 'backpack',
            'label' => __('onboarding.confirm.grade'),
            'value' => $kid->grade_level?->getLabel() ?? __('onboarding.common.dash'),
            'sub' => $kid->school_cycle ? __('onboarding.confirm.cycle', ['cycle' => $kid->school_cycle]) : null,
            'chip' => $kid->classroom,
            'chipIcon' => 'home',
        ])
        @include('onboarding.partials.info-tile', [
            'bg' => '#efe6fb', 'c' => '#9b5de0', 'icon' => 'shield-health',
            'label' => __('onboarding.confirm.allergies'),
            'badge' => $allergyNames ?: __('onboarding.common.none_known'),
            'badgeColor' => '#9b5de0',
        ])
        @include('onboarding.partials.info-tile', [
            'bg' => '#e4eefc', 'c' => '#4d9de0', 'icon' => 'toilet',
            'label' => __('onboarding.confirm.sphincter'),
            'badge' => $kid->sphincter_control?->getLabel() ?? __('onboarding.common.not_specified'),
            'badgeColor' => '#3f8ede',
        ])
    </div>

    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="rounded-2xl border border-[#d9f0e3] bg-[#f2fbf5] px-5 py-4">
            <p class="flex items-center gap-2 text-lg font-bold text-[#2f8f5b]">
                <x-o-icon name="users-group" class="w-6 h-6 shrink-0" stroke="1.7" />
                {{ __('onboarding.confirm.group_title') }}
            </p>
            <p class="mt-2.5">
                @if ($kid->wants_parents_group)
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-[#dcf2e5] px-3 py-1.5 text-sm font-semibold text-[#2f8f5b]">
                        <x-o-icon name="check-badge" class="w-4 h-4 shrink-0" />
                        {{ __('onboarding.confirm.group_yes') }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-400">
                        {{ __('onboarding.confirm.group_no') }}
                    </span>
                @endif
            </p>
            <p class="mt-2.5 text-sm text-slate-400 leading-snug">{{ __('onboarding.confirm.group_text') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-[#fcfcfe] px-5 py-4">
            <p class="flex items-center gap-2 text-lg font-bold text-[#20336b]">
                <x-o-icon name="bell" class="w-6 h-6 shrink-0 text-[#4d9de0]" stroke="1.7" />
                {{ __('onboarding.confirm.notifications_title') }}
            </p>
            <p class="mt-1.5 text-sm text-slate-400 leading-snug">{{ __('onboarding.confirm.notifications_text') }}</p>
            <div class="mt-3">
                @if ($channelIsWhatsApp)
                    <span class="inline-flex items-center gap-2 rounded-xl bg-[#e7f7ee] px-3 py-2">
                        <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-[#22a45d] shrink-0">
                            <x-o-icon name="whatsapp" class="w-5 h-5" />
                        </span>
                        <x-o-icon name="check-badge" class="w-5 h-5 shrink-0 text-[#22a45d]" />
                        <span class="text-sm font-semibold text-[#22a45d] leading-tight">{{ __('onboarding.confirm.channel_whatsapp') }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 rounded-xl bg-[#e8f0fd] px-3 py-2">
                        <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-[#4d9de0] shrink-0">
                            <x-o-icon name="device" class="w-5 h-5" stroke="1.7" />
                        </span>
                        <x-o-icon name="check-badge" class="w-5 h-5 shrink-0 text-[#3f8ede]" />
                        <span class="text-sm font-semibold text-[#2f5bd6] leading-tight">{{ __('onboarding.confirm.channel_screen') }}</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
        <a href="{{ route('onboarding.done', $kid) }}" class="font-display inline-flex items-center justify-center gap-2.5 rounded-2xl bg-[#0f8a3d] hover:bg-[#0c7433] text-white text-base sm:text-lg font-bold px-6 py-4 text-center transition">
            <x-o-icon name="check-circle" class="w-6 h-6 shrink-0" stroke="2" />
            {{ __('onboarding.confirm.confirm_button') }}
        </a>
        <a href="{{ route('onboarding.edit', $kid) }}" class="font-display inline-flex items-center justify-center gap-2.5 rounded-2xl border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-[#20336b] text-base sm:text-lg font-bold px-6 py-4 text-center transition">
            <x-o-icon name="pencil" class="w-6 h-6 shrink-0" stroke="1.8" />
            {{ __('onboarding.confirm.edit_button') }}
        </a>
    </div>

    <div class="mt-4 flex items-start gap-3 rounded-2xl bg-[#f7f8fc] px-5 py-4">
        <span class="w-10 h-10 rounded-full bg-[#e4eefc] flex items-center justify-center text-[#3f8ede] shrink-0">
            <x-o-icon name="lock" class="w-5 h-5" stroke="1.8" />
        </span>
        <span class="text-sm">
            <span class="font-bold text-[#20336b]">{{ __('onboarding.common.secure') }}</span>
            <span class="block text-slate-400">{{ __('onboarding.common.secure_whatsapp') }}</span>
        </span>
    </div>
</div>
@endsection
