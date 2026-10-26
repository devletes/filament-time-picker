# Changelog

All notable changes to `devletes/filament-time-picker` will be documented in this file.

## [1.0.0] - 2026-10-25

First stable release.

### Added

- `TimePicker`: a clock face for Filament's time fields. It extends Filament's own `TimePicker`, so swapping the import is the whole migration, and the state cast, storage format, display format, steps, timezone and validation behave exactly as they do there.
- `DateTimePicker`: the same clock face paired with a calendar. It extends Filament's own `DateTimePicker`; picking a day moves straight to the clock, and a Date / Time switch at the top of the panel moves between them. `minDate()`, `maxDate()`, `disabledDates()`, `firstDayOfWeek()`, `weekStartsOnSunday()`, `defaultFocusedDate()`, `closeOnDateSelection()` and `time(false)` all apply, with month and weekday names in the field's locale.
- The dial follows the field: a display format that writes the hour as 1–12 gets a 12-hour face with AM/PM, anything else a 24-hour face with 13–23 and midnight on an inner ring. `->hoursCycle(12|24)` or `time-picker.hours_cycle` fixes it.
- Every minute is reachable on the dial; `->minutesStep()`, `->hoursStep()` and `->secondsStep()` still coarsen it. `->seconds()` adds a third dial, and a small translatable label above the pin names the unit being set.
- `->minTime()` / `->maxTime()`: a daily window that dims the dial, clamps typing and is enforced again on the server, with a message that names the bound in the field's own time format. On a `DateTimePicker` it narrows further on the days `minDate()` / `maxDate()` fall on.
- The panel's digits are inputs: type a time, or nudge it with the arrow keys by the field's own step.
- `->closeOnComplete()`, `->nowButton()`, `->okLabel()`, `->cancelLabel()` and `->nowLabel()`. The footer uses Filament's own buttons and the calendar's month menu Filament's own dropdown, so both follow the panel's theme.
- `->native()` hands rendering back to Filament for the browser's own control, the better choice on phones.
- No dependencies: vanilla JavaScript on the Alpine Filament already ships, themed off Filament's palette in light and dark. Translatable, with publishable config, views and language files.
- Supports PHP 8.2+, Filament 5, and Laravel 12 and 13, tested in CI on each.
