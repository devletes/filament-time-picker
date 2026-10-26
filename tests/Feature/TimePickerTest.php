<?php

namespace Devletes\TimePicker\Tests\Feature;

use Devletes\TimePicker\Forms\Components\TimePicker;
use Devletes\TimePicker\Tests\Fixtures\PickerComponent;
use Devletes\TimePicker\Tests\TestCase;
use Livewire\Livewire;

class TimePickerTest extends TestCase
{
    public function test_it_renders_the_clock_panel(): void
    {
        Livewire::test(PickerComponent::class, ['time' => '09:30'])
            ->assertSee('Clocked in at')
            ->assertSee('devletesTimePicker', escape: false)
            ->assertSee('fi-dtp-face', escape: false);
    }

    public function test_it_writes_back_in_filaments_internal_format(): void
    {
        $this->assertSame('Y-m-d H:i:s', $this->picker()->getClockConfig()['internalFormat']);
    }

    public function test_it_keeps_the_state_it_was_given(): void
    {
        Livewire::test(PickerComponent::class, ['time' => '09:30'])
            ->assertSchemaStateSet(['clock_in' => '09:30']);
    }

    public function test_a_picked_time_is_saved(): void
    {
        Livewire::test(PickerComponent::class)
            ->fillForm(['clock_in' => '17:45'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true)
            ->assertSchemaStateSet(['clock_in' => '17:45']);
    }

    public function test_native_hands_rendering_back_to_filament(): void
    {
        $this->assertFalse(TimePicker::make('t')->native()->hasView());
        $this->assertTrue(TimePicker::make('t')->hasView());
    }

    public function test_the_dial_is_the_default(): void
    {
        $this->assertFalse(TimePicker::make('t')->isNative());
    }

    public function test_seconds_add_a_third_dial(): void
    {
        $withSeconds = $this->picker()->seconds()->getClockConfig();
        $without = $this->picker()->seconds(false)->getClockConfig();

        $this->assertTrue($withSeconds['hasSeconds']);
        $this->assertFalse($without['hasSeconds']);
    }

    public function test_the_minute_step_reaches_the_panel(): void
    {
        $config = $this->picker()->minutesStep(15)->getClockConfig();

        $this->assertSame(15, $config['minutesStep']);
    }

    public function test_a_step_of_zero_would_stall_the_dial_so_it_is_floored_at_one(): void
    {
        $config = $this->picker()->minutesStep(0)->getClockConfig();

        $this->assertSame(1, $config['minutesStep']);
    }

    public function test_labels_come_from_the_translations_and_can_be_overridden(): void
    {
        $this->assertSame('OK', TimePicker::make('t')->getOkLabel());
        $this->assertSame('Cancel', TimePicker::make('t')->getCancelLabel());
        $this->assertSame('Now', TimePicker::make('t')->getNowLabel());

        $this->assertSame('Confirm', TimePicker::make('t')->okLabel('Confirm')->getOkLabel());
    }

    public function test_the_dial_names_the_unit_it_is_setting(): void
    {
        $labels = $this->picker()->getClockConfig()['labels']['dial'];

        $this->assertSame(['hour' => 'hr', 'minute' => 'min', 'second' => 'sec'], $labels);

        Livewire::test(PickerComponent::class, ['time' => '09:30'])
            ->assertSee('fi-dtp-dial-label', escape: false);
    }

    public function test_the_now_button_can_be_turned_off(): void
    {
        $this->assertTrue(TimePicker::make('t')->hasNowButton());
        $this->assertFalse(TimePicker::make('t')->nowButton(false)->hasNowButton());

        config()->set('time-picker.now_button', false);

        $this->assertFalse(TimePicker::make('t')->hasNowButton());
        $this->assertTrue(TimePicker::make('t')->nowButton()->hasNowButton());
    }

    public function test_bounds_are_normalised_to_wall_clock(): void
    {
        $picker = TimePicker::make('t')->minTime('9am')->maxTime('17:30');

        $this->assertSame('09:00:00', $picker->getMinTime());
        $this->assertSame('17:30:00', $picker->getMaxTime());
        $this->assertNull(TimePicker::make('t')->getMinTime());
    }

    public function test_an_unreadable_bound_is_no_bound(): void
    {
        $this->assertNull(TimePicker::make('t')->minTime('half past something')->getMinTime());
    }

    public function test_a_bound_is_shown_the_way_the_field_shows_times(): void
    {
        $picker = TimePicker::make('t')->displayFormat('g:i A');

        $this->assertSame('9:00 AM', $picker->formatForHumans('09:00:00'));
    }
}
