@extends('onboarding.layout', ['hideHeader' => true])

@section('title', __('onboarding.splash.title'))

@section('content')
<div class="flex min-h-[80vh] w-full max-w-3xl flex-col items-center justify-center text-center">
    <div class="splash-logo">
        <img src="{{ asset('images/onboarding/logo-piedritas-kids.png') }}"
            alt="{{ __('onboarding.common.logo_alt') }}"
            width="786" height="272"
            class="h-28 w-auto max-w-full select-none sm:h-40 lg:h-48" />
    </div>

    <p class="splash-tagline mt-7 max-w-xl text-lg text-[#5e6d92] sm:text-xl">
        {{ __('onboarding.splash.tagline') }}
    </p>

    <a href="{{ route('onboarding.entry') }}"
        class="splash-cta font-display mt-10 inline-flex items-center gap-3 rounded-2xl bg-[#2f5bd6] px-9 py-4 text-lg font-bold text-white transition hover:bg-[#284fc0] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2f5bd6]">
        {{ __('onboarding.splash.cta') }}
        <x-o-icon name="chevron-right" class="h-5 w-5 shrink-0" stroke="2.4" />
    </a>

    <div class="splash-lang mt-12">
        @include('onboarding.partials.lang')
    </div>
</div>
@endsection

@section('scripts')
<style>
    @keyframes splashRise { from { opacity: 0; transform: translateY(14px) scale(.97); } to { opacity: 1; transform: none; } }
    .splash-logo, .splash-tagline, .splash-cta, .splash-lang { animation: splashRise .7s cubic-bezier(.2,.7,.3,1) both; }
    .splash-tagline { animation-delay: .16s; }
    .splash-cta { animation-delay: .3s; }
    .splash-lang { animation-delay: .44s; }
    @media (prefers-reduced-motion: reduce) {
        .splash-logo, .splash-tagline, .splash-cta, .splash-lang { animation: none; }
    }
</style>
@endsection
