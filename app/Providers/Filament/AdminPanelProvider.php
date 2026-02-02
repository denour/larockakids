<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AgeDistributionChart;
use App\Filament\Widgets\AttendanceComparisonChart;
use App\Filament\Widgets\AttendanceStats;
use App\Filament\Widgets\BirthdaysThisMonth;
use App\Filament\Widgets\GenderDistributionChart;
use App\Filament\Widgets\QuarterlyAttendanceChart;
use App\Filament\Widgets\WeeklyAttendanceChart;
use App\Filament\Widgets\YearlyAttendanceChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Vormkracht10\TwoFactorAuth\TwoFactorAuthPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->passwordReset()
            ->profile()
            ->font('Best Kids')
            ->brandName('Piedritas')
            ->favicon(asset('favicon/favicon.ico'))
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('2.5rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->plugins([
                TwoFactorAuthPlugin::make(),
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AttendanceStats::class,
                BirthdaysThisMonth::class,
                AgeDistributionChart::class,
                GenderDistributionChart::class,
                WeeklyAttendanceChart::class,
                QuarterlyAttendanceChart::class,
                YearlyAttendanceChart::class,
                AttendanceComparisonChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.scripts.sticker')
            );
    }
}
