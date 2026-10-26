<?php

namespace Devletes\TimePicker\Tests\Feature;

use Devletes\TimePicker\Forms\Components\DateTimePicker;
use Devletes\TimePicker\Forms\Components\TimePicker;
use Devletes\TimePicker\Tests\Fixtures\DateTimePickerComponent;
use Devletes\TimePicker\Tests\Fixtures\PickerComponent;
use Devletes\TimePicker\Tests\TestCase;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

class DateTimePickerTest extends TestCase
{
    public function test_it_renders_a_calendar_and_the_clock(): void
    {
        Livewire::test(DateTimePickerComponent::class, ['startsAt' => '2026-11-03 09:30:00'])
            ->assertSee('Starts at')
            ->assertSee('devletesTimePicker', escape: false)
            ->assertSee('fi-dtp-calendar-days', escape: false)
            ->assertSee('fi-dtp-face', escape: false)
            ->assertSee('fi-dtp-views', escape: false);
    }

    public function test_a_time_picker_has_no_calendar(): void
    {
        Livewire::test(PickerComponent::class, ['time' => '09:30'])
            ->assertDontSee('fi-dtp-calendar-days', escape: false)
            ->assertDontSee('fi-dtp-views', escape: false);

        $this->assertFalse($this->picker()->getClockConfig()['hasDate']);
    }

    public function test_it_keeps_the_state_it_was_given(): void
    {
        Livewire::test(DateTimePickerComponent::class, ['startsAt' => '2026-11-03 09:30:00'])
            ->assertSchemaStateSet(['starts_at' => '2026-11-03 09:30']);
    }

    public function test_a_picked_date_and_time_is_saved(): void
    {
        Livewire::test(DateTimePickerComponent::class)
            ->fillForm(['starts_at' => '2026-11-03 17:45'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true)
            ->assertSchemaStateSet(['starts_at' => '2026-11-03 17:45']);
    }

    public function test_the_dial_and_the_calendar_are_the_default(): void
    {
        $this->assertFalse(DateTimePicker::make('t')->isNative());
        $this->assertTrue(DateTimePicker::make('t')->hasView());
        $this->assertFalse(DateTimePicker::make('t')->native()->hasView());
    }

    public function test_the_config_describes_a_date_and_a_time(): void
    {
        $config = $this->dateTimePicker()->seconds(false)->getClockConfig();

        $this->assertTrue($config['hasDate']);
        $this->assertTrue($config['hasTime']);
        $this->assertFalse($config['hasSeconds']);
        $this->assertSame('Y-m-d H:i:s', $config['internalFormat']);
        $this->assertSame('M j, Y H:i', $config['displayFormat']);
        $this->assertSame('H:i', $config['timeDisplayFormat']);
    }

    public function test_a_twelve_hour_display_gets_a_twelve_hour_dial(): void
    {
        $config = $this->dateTimePicker()->seconds(false)->displayFormat('M j, Y g:i A')->getClockConfig();

        $this->assertSame(12, $config['hoursCycle']);
        $this->assertSame('g:i A', $config['timeDisplayFormat']);
    }

    public function test_without_time_it_is_a_calendar_only(): void
    {
        $config = $this->dateTimePicker()->time(false)->getClockConfig();

        $this->assertTrue($config['hasDate']);
        $this->assertFalse($config['hasTime']);
        $this->assertFalse($config['hasSeconds']);
    }

    public function test_date_bounds_and_disabled_dates_reach_the_calendar(): void
    {
        $config = $this->dateTimePicker()
            ->minDate('2026-11-01 08:00')
            ->maxDate(Carbon::parse('2026-11-30 18:00'))
            ->disabledDates(['2026-11-11', Carbon::parse('2026-11-25')])
            ->getClockConfig();

        $this->assertSame('2026-11-01 08:00:00', $config['minDate']);
        $this->assertSame('2026-11-30 18:00:00', $config['maxDate']);
        $this->assertSame(['2026-11-11', '2026-11-25'], $config['disabledDates']);
    }

    public function test_the_week_starts_where_filament_says(): void
    {
        $this->assertSame(1, $this->dateTimePicker()->getClockConfig()['firstDayOfWeek']);
        $this->assertSame(0, $this->dateTimePicker()->weekStartsOnSunday()->getClockConfig()['firstDayOfWeek']);
    }

    public function test_a_date_before_the_minimum_is_rejected(): void
    {
        Livewire::test(DateTimePickerComponent::class)
            ->set('minDate', '2026-11-01 08:00:00')
            ->fillForm(['starts_at' => '2026-10-31 09:00'])
            ->call('save')
            ->assertHasErrors('data.starts_at')
            ->assertSet('saved', false);
    }

    public function test_the_daily_window_applies_on_every_day(): void
    {
        Livewire::test(DateTimePickerComponent::class)
            ->set('minTime', '09:00')
            ->set('maxTime', '17:00')
            ->fillForm(['starts_at' => '2026-11-03 08:30'])
            ->call('save')
            ->assertHasErrors('data.starts_at');

        Livewire::test(DateTimePickerComponent::class)
            ->set('minTime', '09:00')
            ->set('maxTime', '17:00')
            ->fillForm(['starts_at' => '2026-11-03 12:00'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true);
    }

    public function test_a_time_bound_is_named_as_a_time_not_a_date(): void
    {
        $picker = $this->dateTimePicker()->seconds(false)->displayFormat('M j, Y g:i A');

        $this->assertSame('6:00 AM', $picker->formatForHumans('06:00:00'));
    }

    public function test_the_time_is_read_out_of_a_full_datetime(): void
    {
        $this->assertSame('17:45:00', $this->dateTimePicker()->seconds(false)->parseState('2026-11-03 17:45'));
    }

    public function test_the_time_picker_still_formats_bounds_with_its_own_display(): void
    {
        $this->assertSame('9:00 AM', TimePicker::make('t')->displayFormat('g:i A')->formatForHumans('09:00:00'));
    }

    protected function dateTimePicker(): DateTimePicker
    {
        return DateTimePicker::make('starts_at')->container(Schema::make(new DateTimePickerComponent));
    }
}
