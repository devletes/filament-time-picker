<?php

namespace Devletes\TimePicker\Tests\Unit;

use Devletes\TimePicker\Forms\Components\TimePicker;
use Devletes\TimePicker\Tests\TestCase;

class HoursCycleTest extends TestCase
{
    public function test_a_twelve_hour_display_format_gets_the_twelve_hour_face(): void
    {
        $this->assertSame(12, TimePicker::make('t')->displayFormat('g:i A')->getHoursCycle());
        $this->assertSame(12, TimePicker::make('t')->displayFormat('h:i a')->getHoursCycle());
    }

    public function test_a_twenty_four_hour_display_format_gets_the_twenty_four_hour_face(): void
    {
        $this->assertSame(24, TimePicker::make('t')->displayFormat('H:i')->getHoursCycle());
        $this->assertSame(24, TimePicker::make('t')->displayFormat('G:i')->getHoursCycle());
    }

    public function test_filaments_own_default_format_gets_the_twenty_four_hour_face(): void
    {
        $this->assertSame(24, TimePicker::make('t')->getHoursCycle());
    }

    public function test_an_escaped_hour_token_is_a_letter_not_a_cycle(): void
    {
        $this->assertSame(24, TimePicker::make('t')->displayFormat('H\\h i')->getHoursCycle());
    }

    public function test_an_explicit_cycle_wins_over_the_format(): void
    {
        $this->assertSame(24, TimePicker::make('t')->displayFormat('g:i A')->hoursCycle(24)->getHoursCycle());
        $this->assertSame(12, TimePicker::make('t')->displayFormat('H:i')->hoursCycle(12)->getHoursCycle());
    }

    public function test_the_config_sets_the_default_for_every_field(): void
    {
        config()->set('time-picker.hours_cycle', 12);

        $this->assertSame(12, TimePicker::make('t')->displayFormat('H:i')->getHoursCycle());

        $this->assertSame(24, TimePicker::make('t')->hoursCycle(24)->getHoursCycle());
    }

    public function test_a_nonsense_cycle_falls_back_to_the_format(): void
    {
        config()->set('time-picker.hours_cycle', 13);

        $this->assertSame(12, TimePicker::make('t')->displayFormat('g:i A')->getHoursCycle());
    }
}
