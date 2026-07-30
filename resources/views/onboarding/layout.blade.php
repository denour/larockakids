<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('onboarding.common.app_name'))</title>
    <link rel="preload" href="{{ asset('fonts/baloo2-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css'])
</head>
{{-- `onboarding-scope` opts every h1/h2/h3 in this section into the rounded display font. --}}
<body class="onboarding-scope h-full antialiased bg-[#f5f6fb] text-slate-700">
    <div class="relative min-h-full overflow-hidden">
        @include('onboarding.partials.background')

        <div class="relative z-10 min-h-screen flex flex-col items-center px-4 py-6 sm:py-8">
            @if (! ($hideHeader ?? false))
                {{-- Three tracks so the wordmark stays optically centred while the back
                     link and the language picker keep their own space at 390px. --}}
                <div class="w-full max-w-7xl grid grid-cols-[1fr_auto_1fr] items-center gap-2 sm:gap-4 mb-6">
                    <div class="flex min-w-0 justify-start">@yield('header-left')</div>
                    <a href="{{ route('onboarding.entry') }}" class="justify-self-center">
                        @include('onboarding.partials.logo')
                    </a>
                    <div class="flex min-w-0 justify-end">@yield('header-right')</div>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
    @yield('scripts')
</body>
</html>
