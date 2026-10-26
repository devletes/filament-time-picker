<?php

namespace Devletes\TimePicker\Concerns;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Support\Carbon;
use Throwable;

trait InteractsWithClock
{
    use HasClockOptions;

    protected function setUpClock(): void
    {
        $this->view('devletes-time-picker::clock');
        $this->native(false);

        // The dial dims out-of-range numerals; the server enforces the bounds, since the panel is not the only way in.
        $this->rule(
            static function (self $component): Closure {
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
            static fn (self $component): bool => filled($component->getMinTime()) || filled($component->getMaxTime()),
        );
    }

    /** No view sends rendering through Filament's embedded HTML, and with it the native input. */
    public function hasView(): bool
    {
        return (! $this->isNative()) && parent::hasView();
    }

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

    public function formatForHumans(string $time): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', $time)->format($this->getTimeDisplayFormat());
        } catch (InvalidFormatException) {
            return $time;
        }
    }

    public function getTimeDisplayFormat(): string
    {
        if (! $this->hasDate()) {
            return $this->getDisplayFormat();
        }

        $seconds = $this->hasSeconds() ? ':s' : '';

        return $this->getHoursCycle() === 12 ? "g:i{$seconds} A" : "H:i{$seconds}";
    }

    /**
     * @return array<string, mixed>
     */
    public function getClockConfig(): array
    {
        return [
            // Filament keeps a non-native picker's state as a whole datetime, even for a time-only field.
            'internalFormat' => $this->getInternalFormat(),
            'displayFormat' => $this->getDisplayFormat(),
            'timeDisplayFormat' => $this->getTimeDisplayFormat(),
            'hasDate' => $this->hasDate(),
            'hasTime' => $this->hasTime(),
            'hoursCycle' => $this->getHoursCycle(),
            'hasSeconds' => $this->hasTime() && $this->hasSeconds(),
            'hoursStep' => max(1, $this->getHoursStep()),
            'minutesStep' => max(1, $this->getMinutesStep()),
            'secondsStep' => max(1, $this->getSecondsStep()),
            'minTime' => $this->getMinTime(),
            'maxTime' => $this->getMaxTime(),
            'minDate' => $this->hasDate() ? $this->normaliseDateTime($this->getMinDate()) : null,
            'maxDate' => $this->hasDate() ? $this->normaliseDateTime($this->getMaxDate()) : null,
            'disabledDates' => $this->hasDate() ? $this->getDisabledDatesAsStrings() : [],
            'defaultFocusedDate' => $this->hasDate() ? $this->normaliseDateTime($this->getDefaultFocusedDate()) : null,
            'firstDayOfWeek' => $this->getFirstDayOfWeek() % 7,
            'closeOnDateSelection' => $this->shouldCloseOnDateSelection(),
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
                'date' => __('devletes-time-picker::time-picker.date'),
                'time' => __('devletes-time-picker::time-picker.time'),
                'hour' => __('devletes-time-picker::time-picker.hour'),
                'minute' => __('devletes-time-picker::time-picker.minute'),
                'second' => __('devletes-time-picker::time-picker.second'),
                'dial' => [
                    'hour' => __('devletes-time-picker::time-picker.dial.hour'),
                    'minute' => __('devletes-time-picker::time-picker.dial.minute'),
                    'second' => __('devletes-time-picker::time-picker.dial.second'),
                ],
                'selectHour' => __('devletes-time-picker::time-picker.select_hour'),
                'selectMinute' => __('devletes-time-picker::time-picker.select_minute'),
                'selectSecond' => __('devletes-time-picker::time-picker.select_second'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getClockFace(): array
    {
        return [
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

    protected function normaliseDateTime(CarbonInterface|string|null $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return ($value instanceof CarbonInterface ? $value : Carbon::parse($value))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function getDisabledDatesAsStrings(): array
    {
        return collect($this->getDisabledDates())
            ->map(fn (mixed $date): ?string => $this->normaliseDateTime($date instanceof \DateTimeInterface ? Carbon::instance($date) : (string) $date))
            ->filter()
            ->map(fn (string $date): string => substr($date, 0, 10))
            ->values()
            ->all();
    }
}
