<?php

namespace Devletes\TimePicker\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Devletes\TimePicker\Forms\Components\TimePicker;
use Devletes\TimePicker\Tests\Fixtures\PickerComponent;
use Devletes\TimePicker\TimePickerServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Schemas\Schema;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            TablesServiceProvider::class,
            NotificationsServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            // Filament rebinds Livewire's DataStore with a plain bind(), so
            // Livewire has to register afterwards for its binding to win.
            LivewireServiceProvider::class,
            TimePickerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.timezone', 'UTC');
    }

    /**
     * A picker attached to a schema. Several of the field's getters read the
     * container — whether it is disabled, whether it is focused — so a bare
     * instance cannot answer them.
     */
    protected function picker(string $name = 'clock_in'): TimePicker
    {
        return TimePicker::make($name)->container(Schema::make(new PickerComponent));
    }
}
