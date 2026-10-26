<?php

namespace Workbench\App\Filament\Pages;

use BackedEnum;
use Devletes\TimePicker\Forms\Components\DateTimePicker;
use Devletes\TimePicker\Forms\Components\TimePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** One section per picker variant; the screenshot script finds each by its data-demo attribute. */
class Showcase extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Deliveries';

    protected static ?string $title = 'Schedule a delivery';

    protected static ?string $slug = 'deliveries';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'deliver_at' => '14:30',
            'cutoff' => '17:00',
            'pickup' => '11:15',
            'ships_at' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s'),
            'loading_time' => '08:45:30',
            'call_back_at' => '16:00',
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('form')]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Section::make('Delivery slot')
                        ->extraAttributes(['data-demo' => 'twelve-hour'])
                        ->schema([
                            TimePicker::make('deliver_at')
                                ->label('Deliver at')
                                ->seconds(false)
                                ->displayFormat('g:i A'),
                        ]),

                    Section::make('Same-day cutoff')
                        ->extraAttributes(['data-demo' => 'twenty-four-hour'])
                        ->schema([
                            TimePicker::make('cutoff')
                                ->label('Orders close at')
                                ->seconds(false)
                                ->displayFormat('H:i'),
                        ]),

                    Section::make('Store pickup')
                        ->extraAttributes(['data-demo' => 'bounds'])
                        ->schema([
                            TimePicker::make('pickup')
                                ->label('Pickup time')
                                ->seconds(false)
                                ->displayFormat('g:i A')
                                ->minTime('09:00')
                                ->maxTime('17:00')
                                ->helperText('Open 9 AM to 5 PM'),
                        ]),

                    Section::make('Dispatch')
                        ->extraAttributes(['data-demo' => 'date-time'])
                        ->schema([
                            DateTimePicker::make('ships_at')
                                ->label('Ships at')
                                ->seconds(false)
                                ->displayFormat('M j, Y g:i A')
                                ->minDate(now()->startOfDay())
                                ->minTime('08:00')
                                ->maxTime('20:00'),
                        ]),

                    Section::make('Courier call-back')
                        ->extraAttributes(['data-demo' => 'close-on-complete'])
                        ->schema([
                            TimePicker::make('call_back_at')
                                ->label('Call the courier at')
                                ->seconds(false)
                                ->displayFormat('g:i A')
                                ->closeOnComplete()
                                ->helperText('Picking a minute saves and closes'),
                        ]),

                    Section::make('Loading dock')
                        ->extraAttributes(['data-demo' => 'seconds'])
                        ->schema([
                            TimePicker::make('loading_time')
                                ->label('Truck loaded at')
                                ->seconds()
                                ->displayFormat('H:i:s'),
                        ]),
                ]),
            ])
            ->statePath('data');
    }
}
