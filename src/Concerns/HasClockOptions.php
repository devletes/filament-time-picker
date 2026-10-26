<?php

namespace Devletes\TimePicker\Concerns;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Carbon;

trait HasClockOptions
{
    protected int|Closure|null $hoursCycle = null;

    protected CarbonInterface|string|Closure|null $minTime = null;

    protected CarbonInterface|string|Closure|null $maxTime = null;

    protected string|Closure|null $okLabel = null;

    protected string|Closure|null $cancelLabel = null;

    protected string|Closure|null $nowLabel = null;

    protected bool|Closure|null $hasNowButton = null;

    protected bool|Closure|null $shouldCloseOnComplete = null;

    public function hoursCycle(int|Closure|null $cycle): static
    {
        $this->hoursCycle = $cycle;

        return $this;
    }

    public function minTime(CarbonInterface|string|Closure|null $time): static
    {
        $this->minTime = $time;

        return $this;
    }

    public function maxTime(CarbonInterface|string|Closure|null $time): static
    {
        $this->maxTime = $time;

        return $this;
    }

    public function okLabel(string|Closure|null $label): static
    {
        $this->okLabel = $label;

        return $this;
    }

    public function cancelLabel(string|Closure|null $label): static
    {
        $this->cancelLabel = $label;

        return $this;
    }

    public function nowLabel(string|Closure|null $label): static
    {
        $this->nowLabel = $label;

        return $this;
    }

    public function nowButton(bool|Closure|null $condition = true): static
    {
        $this->hasNowButton = $condition;

        return $this;
    }

    public function closeOnComplete(bool|Closure|null $condition = true): static
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

        // Unset: follow the display format's hour cycle.
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

    /** `g`/`h` are the 12-hour tokens; a backslash (chr 92) escapes the next character. */
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

    protected function normaliseTime(CarbonInterface|string|null $time): ?string
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
