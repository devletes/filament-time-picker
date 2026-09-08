<?php

namespace Devletes\TimePicker\Tests\Feature;

use Devletes\TimePicker\Tests\Fixtures\PickerComponent;
use Devletes\TimePicker\Tests\TestCase;
use Livewire\Livewire;

/**
 * A bound the pointer honours is a courtesy — the dial dims what it will not
 * take. A bound the server honours is the constraint, because the panel is
 * not the only way a value arrives.
 */
class BoundsTest extends TestCase
{
    public function test_a_time_inside_the_bounds_saves(): void
    {
        Livewire::test(PickerComponent::class)
            ->set('minTime', '09:00')
            ->set('maxTime', '17:00')
            ->fillForm(['clock_in' => '13:30'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true);
    }

    public function test_a_time_before_the_minimum_is_rejected(): void
    {
        Livewire::test(PickerComponent::class)
            ->set('minTime', '09:00')
            ->fillForm(['clock_in' => '08:59'])
            ->call('save')
            ->assertHasErrors('data.clock_in')
            ->assertSet('saved', false);
    }

    public function test_a_time_after_the_maximum_is_rejected(): void
    {
        Livewire::test(PickerComponent::class)
            ->set('maxTime', '17:00')
            ->fillForm(['clock_in' => '17:01'])
            ->call('save')
            ->assertHasErrors('data.clock_in')
            ->assertSet('saved', false);
    }

    public function test_the_edges_of_the_range_are_allowed(): void
    {
        Livewire::test(PickerComponent::class)
            ->set('minTime', '09:00')
            ->set('maxTime', '17:00')
            ->fillForm(['clock_in' => '09:00'])
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(PickerComponent::class)
            ->set('minTime', '09:00')
            ->set('maxTime', '17:00')
            ->fillForm(['clock_in' => '17:00'])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_an_empty_field_is_not_out_of_bounds(): void
    {
        Livewire::test(PickerComponent::class)
            ->set('minTime', '09:00')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_the_message_names_the_bound_in_the_fields_own_format(): void
    {
        Livewire::test(PickerComponent::class)
            ->set('minTime', '09:00')
            ->set('displayFormat', 'g:i A')
            ->fillForm(['clock_in' => '08:00'])
            ->call('save')
            ->assertHasErrors('data.clock_in')
            ->assertSee('must be no earlier than 9:00 AM');
    }

    public function test_no_bounds_means_no_rule(): void
    {
        Livewire::test(PickerComponent::class)
            ->fillForm(['clock_in' => '03:00'])
            ->call('save')
            ->assertHasNoErrors();
    }
}
