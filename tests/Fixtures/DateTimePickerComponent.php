<?php

namespace Devletes\TimePicker\Tests\Fixtures;

use Devletes\TimePicker\Forms\Components\DateTimePicker;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

class DateTimePickerComponent extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public ?string $minDate = null;

    public ?string $maxDate = null;

    public ?string $minTime = null;

    public ?string $maxTime = null;

    public ?string $displayFormat = null;

    public bool $seconds = false;

    public bool $time = true;

    public bool $saved = false;

    public function mount(?string $startsAt = null): void
    {
        $startsAt === null
            ? $this->form->fill()
            : $this->form->fill(['starts_at' => $startsAt]);
    }

    public function save(): void
    {
        $this->form->getState();

        $this->saved = true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('starts_at')
                    ->label('Starts at')
                    ->seconds(fn (): bool => $this->seconds)
                    ->time(fn (): bool => $this->time)
                    ->displayFormat(fn (): ?string => $this->displayFormat)
                    ->minDate(fn (): ?string => $this->minDate)
                    ->maxDate(fn (): ?string => $this->maxDate)
                    ->minTime(fn (): ?string => $this->minTime)
                    ->maxTime(fn (): ?string => $this->maxTime),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                {{ $this->form }}
            </div>
        BLADE;
    }
}
