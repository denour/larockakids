@extends('onboarding.layout')

@section('title', __('onboarding.form.register_title'))

@section('header-left')
    <a href="{{ route('onboarding.search') }}" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-[#2f5bd6] hover:text-[#1f47b3]">
        <x-o-icon name="chevron-left" class="w-4 h-4 shrink-0" stroke="2.2" />
        <span class="hidden sm:inline">{{ __('onboarding.form.back_to_search') }}</span>
    </a>
@endsection

@section('header-right')
    @include('onboarding.partials.lang')
@endsection

@section('content')
<form method="POST" action="{{ route('onboarding.store') }}" class="w-full max-w-7xl">
    @csrf

    <div class="mb-4 flex flex-col items-center gap-4 rounded-2xl border border-[#edeff7] bg-white px-6 py-6 text-center shadow-[0_10px_30px_-22px_rgba(80,80,140,0.5)] sm:flex-row sm:justify-center sm:text-left">
        <span class="w-16 h-16 shrink-0 rounded-full bg-[#f2e9fd] flex items-center justify-center text-[#8b5cf6]">
            <x-o-icon name="user-plus" class="w-8 h-8" stroke="1.6" />
        </span>
        <div class="max-w-xl">
            <h1 class="text-[22px] font-extrabold text-[#20336b]">{{ __('onboarding.form.register_heading') }}</h1>
            <p class="mt-1 text-[13px] leading-snug text-slate-400">{{ __('onboarding.form.register_lead') }}</p>
        </div>
    </div>

    @include('onboarding.partials.kid-form-fields', ['kid' => null, 'numbered' => true, 'allergyOptions' => $allergyOptions])

    {{-- In flow (not `fixed`) so it can never sit on top of the last two cards. --}}
    <div class="mt-4 flex flex-col-reverse gap-3 rounded-2xl border border-[#edeff7] bg-white px-5 py-4 shadow-[0_6px_20px_-14px_rgba(80,80,140,0.45)] sm:flex-row sm:justify-end">
        <a href="{{ route('onboarding.search') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-6 py-3 text-[14px] font-bold text-[#20336b] transition hover:bg-slate-50">{{ __('onboarding.common.cancel') }}</a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7a3ff2] px-6 py-3 text-[14px] font-bold text-white transition hover:bg-[#6a34da]">
            <x-o-icon name="save" class="w-[18px] h-[18px] shrink-0" stroke="1.8" />
            {{ __('onboarding.form.register_submit') }}
        </button>
    </div>

    <p class="mt-4 flex flex-wrap items-center justify-center gap-x-1.5 gap-y-1 px-4 text-center text-[12px] text-slate-400">
        <x-o-icon name="lock" class="w-4 h-4 shrink-0" stroke="1.6" />
        <span>{!! __('onboarding.form.terms', ['link' => '<span class="font-semibold text-[#2f5bd6]">'.e(__('onboarding.form.terms_link')).'</span>']) !!}</span>
    </p>
</form>
@endsection
