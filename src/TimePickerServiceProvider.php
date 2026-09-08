<?php

namespace Devletes\TimePicker;

use Devletes\TimePicker\Assets\HashedCss;
use Devletes\TimePicker\Assets\HashedJs;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class TimePickerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/time-picker.php', 'time-picker');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'devletes-time-picker');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'devletes-time-picker');

        $this->publishes([
            __DIR__.'/../config/time-picker.php' => config_path('time-picker.php'),
        ], 'devletes-time-picker-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/devletes-time-picker'),
        ], 'devletes-time-picker-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/devletes-time-picker'),
        ], 'devletes-time-picker-translations');

        FilamentAsset::register([
            HashedCss::make('devletes-time-picker', __DIR__.'/../resources/css/time-picker.css'),
            HashedJs::make('devletes-time-picker', __DIR__.'/../resources/js/time-picker.js'),
        ], package: 'devletes/filament-time-picker');
    }
}
