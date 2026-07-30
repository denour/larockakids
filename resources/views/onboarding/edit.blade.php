@extends('onboarding.layout')

@section('title', __('onboarding.form.edit_title'))

@section('header-left')
    <a href="{{ route('onboarding.confirm', $kid) }}" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-[#2f5bd6] hover:text-[#1f47b3]">
        <x-o-icon name="chevron-left" class="w-4 h-4 shrink-0" stroke="2.2" />
        <span class="hidden sm:inline">{{ __('onboarding.form.back_to_summary') }}</span>
    </a>
@endsection

@section('header-right')
    @include('onboarding.partials.lang')
@endsection

@section('content')
<form method="POST" action="{{ route('onboarding.update', $kid) }}" class="w-full max-w-7xl">
    @csrf
    @method('PUT')

    <div class="mb-5 mt-1 sm:-mt-2 text-center">
        <h1 class="text-[22px] sm:text-[26px] font-extrabold text-[#20336b]">{{ __('onboarding.form.edit_heading') }}</h1>
        <p class="mt-1 text-[13px] text-slate-400">{{ __('onboarding.form.edit_lead') }}</p>
    </div>

    @include('onboarding.partials.kid-form-fields', ['kid' => $kid, 'numbered' => false, 'allergyOptions' => $allergyOptions])

    {{-- In flow (not `fixed`) so it can never sit on top of the last two cards. --}}
    <div class="mt-4 flex flex-col-reverse gap-3 rounded-2xl border border-[#edeff7] bg-white px-5 py-4 shadow-[0_6px_20px_-14px_rgba(80,80,140,0.45)] sm:flex-row sm:justify-end">
        <a href="{{ route('onboarding.confirm', $kid) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-6 py-3 text-[14px] font-bold text-[#20336b] transition hover:bg-slate-50">{{ __('onboarding.common.cancel') }}</a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7a3ff2] px-6 py-3 text-[14px] font-bold text-white transition hover:bg-[#6a34da]">
            <x-o-icon name="save" class="w-[18px] h-[18px] shrink-0" stroke="1.8" />
            {{ __('onboarding.form.edit_submit') }}
        </button>
    </div>
</form>
@endsection
