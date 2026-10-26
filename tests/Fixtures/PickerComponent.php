<?php

namespace Devletes\TimePicker\Tests\Fixtures;

use Devletes\TimePicker\Forms\Components\TimePicker;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

class PickerComponent extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public ?string $minTime = null;

    public ?string $maxTime = null;

    public ?string $displayFormat = null;

    public ?int $hoursCycle = null;

    public bool $seconds = false;

    public bool $native = false;

    public bool $saved = false;

    public function mount(?string $time = null): void
    {
        $time === null
            ? $this->form->fill()
            : $this->form->fill(['clock_in' => $time]);
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
                TimePicker::make('clock_in')
                    ->label('Clocked in at')
                    ->seconds(fn (): bool => $this->seconds)
                    ->native(fn (): bool => $this->native)
                    ->displayFormat(fn (): ?string => $this->displayFormat)
                    ->hoursCycle(fn (): ?int => $this->hoursCycle)
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
