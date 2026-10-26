<?php

namespace Devletes\TimePicker\Forms\Components;

use Devletes\TimePicker\Concerns\InteractsWithClock;
use Filament\Forms\Components\TimePicker as BaseTimePicker;

class TimePicker extends BaseTimePicker
{
    use InteractsWithClock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpClock();
    }
}
