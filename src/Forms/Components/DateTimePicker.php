<?php

namespace Devletes\TimePicker\Forms\Components;

use Devletes\TimePicker\Concerns\InteractsWithClock;
use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;

class DateTimePicker extends BaseDateTimePicker
{
    use InteractsWithClock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpClock();
    }
}
