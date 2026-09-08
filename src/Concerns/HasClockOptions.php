<?php

namespace Devletes\TimePicker\Concerns;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Carbon;

/**
 * Everything the dial adds on top of Filament's own time picker. The
 * inherited options — seconds, steps, display format, timezone, locale —
 * are read as they are and are not restated here.
 */
trait HasClockOptions
{
    protected int | Closure | null $hoursCycle = null;

    protected CarbonInterface | string | Closure | null $minTime = null;

    protected CarbonInterface | string | Closure | null $maxTime = null;

    protected string | Closure | null $okLabel = null;

    protected string | Closure | null $cancelLabel = null;

    protected string | Closure | null $nowLabel = null;

    protected bool | Closure | null $hasNowButton = null;

    protected bool | Closure | null $shouldCloseOnComplete = null;

    /**
     * 12 for a single ring of 1–12 with an AM/PM switch, 24 for two rings
     * with 13–00 on the inside.
     */
    public function hoursCycle(int | Closure | null $cycle): static
    {
        $this->hoursCycle = $cycle;

        return $this;
    }

    /**
     * The earliest time that may be picked. Numerals outside the range are
     * dimmed and refuse the pointer, and the same bound is enforced again on
     * the server — see {@see \Devletes\TimePicker\Forms\Components\TimePicker::setUp()}.
     */
    public function minTime(CarbonInterface | string | Closure | null $time): static
    {
        $this->minTime = $time;

        return $this;
    }

    /** The latest time that may be picked. */
    public function maxTime(CarbonInterface | string | Closure | null $time): static
    {
        $this->maxTime = $time;

        return $this;
    }

    public function okLabel(string | Closure | null $label): static
    {
        $this->okLabel = $label;

        return $this;
    }

    public function cancelLabel(string | Closure | null $label): static
    {
        $this->cancelLabel = $label;

        return $this;
    }

    public function nowLabel(string | Closure | null $label): static
    {
        $this->nowLabel = $label;

        return $this;
    }

    /** The footer shortcut that fills in the current time. */
    public function nowButton(bool | Closure | null $condition = true): static
    {
        $this->hasNowButton = $condition;

        return $this;
    }

    /**
     * Commit and close as soon as the last dial is set, instead of waiting
     * for the panel to be confirmed.
     */
    public function closeOnComplete(bool | Closure | null $condition = true): static
    {
        $this->shouldCloseOnComplete = $condition;

        return $this;
    }

    public function getHoursCycle(): int
    {
        $cycle = $this->evaluate($this->hoursCycle)
            ?? config('time-picker.hours_cycle');

        if (in_array($cycle, [12, 24], strict: true)) {
            return $cycle;
        }

        // Nothing said, so the dial follows the field: a format that writes
        // the hour as 1–12 gets the 12-hour face, everything else the 24.
        return $this->displayFormatIsTwelveHour() ? 12 : 24;
    }

    public function getMinTime(): ?string
    {
        return $this->normaliseTime($this->evaluate($this->minTime));
    }

    public function getMaxTime(): ?string
    {
        return $this->normaliseTime($this->evaluate($this->maxTime));
    }

    public function getOkLabel(): string
    {
        return $this->evaluate($this->okLabel) ?? __('devletes-time-picker::time-picker.ok');
    }

    public function getCancelLabel(): string
    {
        return $this->evaluate($this->cancelLabel) ?? __('devletes-time-picker::time-picker.cancel');
    }

    public function getNowLabel(): string
    {
        return $this->evaluate($this->nowLabel) ?? __('devletes-time-picker::time-picker.now');
    }

    public function hasNowButton(): bool
    {
        return (bool) ($this->evaluate($this->hasNowButton) ?? config('time-picker.now_button', true));
    }

    public function shouldCloseOnComplete(): bool
    {
        return (bool) ($this->evaluate($this->shouldCloseOnComplete) ?? config('time-picker.close_on_complete', false));
    }

    /**
     * Whether the display format writes the hour as 1–12. `g` and `h` are the
     * 12-hour tokens; a backslash escapes the character after it, so `\h` is
     * the letter h and says nothing about the cycle.
     */
    protected function displayFormatIsTwelveHour(): bool
    {
        $format = $this->getDisplayFormat();
        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {
            if ($format[$i] === chr(92)) {
                $i++;

                continue;
            }

            if (in_array($format[$i], ['g', 'h'], strict: true)) {
                return true;
            }
        }

        return false;
    }

    /** Bounds are held as wall-clock `H:i:s`, whatever they were given as. */
    protected function normaliseTime(CarbonInterface | string | null $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        if ($time instanceof CarbonInterface) {
            return $time->format('H:i:s');
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
