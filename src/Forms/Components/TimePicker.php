<?php

namespace Devletes\TimePicker\Forms\Components;

use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Devletes\TimePicker\Concerns\HasClockOptions;
use Filament\Forms\Components\TimePicker as BaseTimePicker;
use Illuminate\Support\Carbon;

/**
 * Filament's time picker with a clock face.
 *
 * A drop-in replacement: it extends Filament's own component, so the state
 * cast, the storage format, the display format, seconds, the steps, the
 * timezone and every field concern behave exactly as they do there — only
 * the panel is ours. `->native()` hands rendering back to Filament and gives
 * the browser's own input, which is the sensible thing on a phone.
 *
 * @see \Devletes\TimePicker\Concerns\HasClockOptions for what the dial adds.
 */
class TimePicker extends BaseTimePicker
{
    use HasClockOptions;

    protected string $view = 'devletes-time-picker::clock';

    protected function setUp(): void
    {
        parent::setUp();

        // Filament's picker defaults to the browser's input; ours is the
        // whole reason the package exists, so the dial is the default here.
        $this->native(false);

        // A bound the pointer honours is a courtesy. A bound the server
        // honours is the constraint — the panel is not the only way in.
        $this->rule(
            static function (TimePicker $component): Closure {
                return static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                    $time = $component->parseState($value);

                    if ($time === null) {
                        return;
                    }

                    $min = $component->getMinTime();
                    $max = $component->getMaxTime();

                    if (filled($min) && $time < $min) {
                        $fail(__('devletes-time-picker::time-picker.validation.min', [
                            'time' => $component->formatForHumans($min),
                        ]));
                    }

                    if (filled($max) && $time > $max) {
                        $fail(__('devletes-time-picker::time-picker.validation.max', [
                            'time' => $component->formatForHumans($max),
                        ]));
                    }
                };
            },
            static fn (TimePicker $component): bool => filled($component->getMinTime()) || filled($component->getMaxTime()),
        );
    }

    /**
     * Falling back to no view is what sends rendering through Filament's own
     * embedded HTML, and with it the native input.
     */
    public function hasView(): bool
    {
        return (! $this->isNative()) && parent::hasView();
    }

    /**
     * The field's state as `H:i:s`, or null if it is empty or unreadable.
     * Read by the bounds rule, and useful to anything else comparing times.
     */
    public function parseState(mixed $value): ?string
    {
        if (blank($value) || ! is_string($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat($this->getFormat(), $value)->format('H:i:s');
        } catch (InvalidFormatException) {
            try {
                return Carbon::parse($value)->format('H:i:s');
            } catch (InvalidFormatException) {
                return null;
            }
        }
    }

    /** An `H:i:s` time written the way this field displays times. */
    public function formatForHumans(string $time): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', $time)->format($this->getDisplayFormat());
        } catch (InvalidFormatException) {
            return $time;
        }
    }

    /**
     * Everything the panel needs, resolved once per render. Closures are
     * evaluated here rather than in the view so the Blade stays declarative.
     *
     * @return array<string, mixed>
     */
    public function getClockConfig(): array
    {
        return [
            // What the panel writes back. Filament keeps a non-native picker's
            // state as a whole datetime even when only a time is being edited,
            // so the date rides along untouched rather than being invented.
            'internalFormat' => $this->getInternalFormat(),
            'displayFormat' => $this->getDisplayFormat(),
            'hoursCycle' => $this->getHoursCycle(),
            'hasSeconds' => $this->hasSeconds(),
            'hoursStep' => max(1, $this->getHoursStep()),
            'minutesStep' => max(1, $this->getMinutesStep()),
            'secondsStep' => max(1, $this->getSecondsStep()),
            'minTime' => $this->getMinTime(),
            'maxTime' => $this->getMaxTime(),
            'closeOnComplete' => $this->shouldCloseOnComplete(),
            'isDisabled' => $this->isDisabled(),
            'isReadOnly' => $this->isReadOnly(),
            'isAutofocused' => $this->isAutofocused(),
            'locale' => $this->getLocale(),
            'labels' => [
                'meridiem' => [
                    'am' => __('devletes-time-picker::time-picker.meridiem.am'),
                    'pm' => __('devletes-time-picker::time-picker.meridiem.pm'),
                ],
                'hour' => __('devletes-time-picker::time-picker.hour'),
                'minute' => __('devletes-time-picker::time-picker.minute'),
                'second' => __('devletes-time-picker::time-picker.second'),
                'selectHour' => __('devletes-time-picker::time-picker.select_hour'),
                'selectMinute' => __('devletes-time-picker::time-picker.select_minute'),
                'selectSecond' => __('devletes-time-picker::time-picker.select_second'),
            ],
        ];
    }

    /**
     * The stage rings, drawn server-side so the panel is complete on first
     * paint and the SVG is not rebuilt on every open.
     *
     * @return array<string, mixed>
     */
    public function getClockFace(): array
    {
        return [
            // 12 sits at the top of the outer ring and midnight at the top of
            // the inner one, so a numeral's position is its index times 30deg.
            'hour' => $this->getHoursCycle() === 12
                ? [$this->ring([12, ...range(1, 11)], outer: true, pad: false)]
                : [
                    $this->ring([12, ...range(1, 11)], outer: true, pad: false),
                    $this->ring([0, ...range(13, 23)], outer: false, pad: true),
                ],
            'minute' => [$this->ring(range(0, 55, 5), outer: true, pad: true)],
            'second' => [$this->ring(range(0, 55, 5), outer: true, pad: true)],
        ];
    }

    /**
     * One ring of numerals, placed clockwise from the top. Twelve positions,
     * 30° apart, on a circle inside the face's 224-unit box.
     *
     * @param  array<int, int>  $values
     * @return array<int, array<string, mixed>>
     */
    protected function ring(array $values, bool $outer, bool $pad): array
    {
        $centre = 112;
        $radius = $outer ? 84 : 54;

        return collect($values)
            ->values()
            ->map(fn (int $value, int $index): array => [
                'value' => $value,
                'label' => $pad ? str_pad((string) $value, 2, '0', STR_PAD_LEFT) : (string) $value,
                'x' => round($centre + $radius * sin(deg2rad($index * 30)), 2),
                'y' => round($centre - $radius * cos(deg2rad($index * 30)), 2),
                'outer' => $outer,
            ])
            ->all();
    }
}
