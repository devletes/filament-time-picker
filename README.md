# Filament Time Picker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devletes/filament-time-picker.svg?style=flat-square)](https://packagist.org/packages/devletes/filament-time-picker)
[![Total Downloads](https://img.shields.io/packagist/dt/devletes/filament-time-picker.svg?style=flat-square)](https://packagist.org/packages/devletes/filament-time-picker)
[![License](https://img.shields.io/packagist/l/devletes/filament-time-picker.svg?style=flat-square)](https://packagist.org/packages/devletes/filament-time-picker)
[![GitHub Stars](https://img.shields.io/github/stars/devletes/filament-time-picker?style=flat-square)](https://github.com/devletes/filament-time-picker/stargazers)

A clock face for Filament's time and date-time fields — hour and minute dials, drag or tap or type.

- **Drop-in replacements.** `TimePicker` and `DateTimePicker` extend Filament's own, so swapping the import is the whole migration
- **No dependencies.** No jQuery, no date library, no build step — vanilla JavaScript on the Alpine you already have
- **The dial follows the field.** `g:i A` gets a 12-hour face with AM/PM, `H:i` gets a 24-hour face with an inner ring
- **Every minute is reachable**, not every fifth, and the steps still coarsen it when you want that
- **Bounds that hold.** `minTime()` / `maxTime()` dim the dial, clamp typing, and are re-checked on the server
- **A calendar to match**, with min and max dates, disabled dates and your locale's month and weekday names
- Themed off Filament's palette — follows your panel's colour, light and dark

<table><tr>
<td width="50%"><img src="docs/images/layout_light.png" alt="The clock open on a form (light)"></td>
<td width="50%"><img src="docs/images/layout_dark.png" alt="The clock open on a form (dark)"></td>
</tr></table>

## Requirements

- PHP `^8.2`
- Filament `^5.0` on Laravel 12 or 13

## Installation

```bash
composer require devletes/filament-time-picker
```

Then publish Filament's assets so the stylesheet and script are served on your panels:

```bash
php artisan filament:assets
```

The package registers its own stylesheet and script with Filament's asset manager — there is nothing to add to your custom Tailwind theme.

## Quick start

```php
use Devletes\TimePicker\Forms\Components\DateTimePicker;
use Devletes\TimePicker\Forms\Components\TimePicker;

TimePicker::make('deliver_at')
    ->seconds(false)
    ->displayFormat('g:i A');

DateTimePicker::make('ships_at')
    ->seconds(false)
    ->displayFormat('M j, Y g:i A');
```

## A drop-in replacement

Both components **extend** Filament's own, so swapping the import is the whole migration:

```diff
-use Filament\Forms\Components\TimePicker;
+use Devletes\TimePicker\Forms\Components\TimePicker;

-use Filament\Forms\Components\DateTimePicker;
+use Devletes\TimePicker\Forms\Components\DateTimePicker;
```

Everything you already use still works and still means the same thing — `seconds()`, the steps, `format()`, `displayFormat()`, `timezone()`, `live()`, `disabled()`, the affixes and the validation. The state cast is Filament's own, so nothing about what is stored changes.

## The dial follows the field

Say nothing, and the face is read off the display format. A field that writes its hour as 1–12 gets the 12-hour face with an AM/PM switch; anything else gets the 24-hour face, with 13–23 and midnight on an inner ring.

```php
TimePicker::make('deliver_at')->displayFormat('g:i A');   // 12-hour face
TimePicker::make('cutoff')->displayFormat('H:i');         // 24-hour face
TimePicker::make('cutoff')->hoursCycle(24);               // say so yourself
```

<table><tr>
<td width="50%"><img src="docs/images/twelve_hour_light.png" alt="The 12-hour face (light)"></td>
<td width="50%"><img src="docs/images/twelve_hour_dark.png" alt="The 12-hour face (dark)"></td>
</tr></table>

<table><tr>
<td width="50%"><img src="docs/images/twenty_four_hour_light.png" alt="The 24-hour face with its inner ring (light)"></td>
<td width="50%"><img src="docs/images/twenty_four_hour_dark.png" alt="The 24-hour face with its inner ring (dark)"></td>
</tr></table>

Set `time-picker.hours_cycle` in the config to make one face the rule everywhere.

## Minutes and seconds

Picking an hour moves to the minute dial. The dial takes **every minute**: the numerals are drawn at the fives, but dragging between them lands on 9:07 the way you would expect. A small label above the pin says which unit you are setting.

```php
TimePicker::make('slot')->minutesStep(15);   // quarter hours only
TimePicker::make('slot')->hoursStep(2);
```

<table><tr>
<td width="50%"><img src="docs/images/minutes_light.png" alt="The minute dial (light)"></td>
<td width="50%"><img src="docs/images/minutes_dark.png" alt="The minute dial (dark)"></td>
</tr></table>

`seconds()` adds a third dial, and `secondsStep()` coarsens it the same way.

```php
TimePicker::make('loaded_at')
    ->seconds()
    ->displayFormat('H:i:s');
```

<table><tr>
<td width="50%"><img src="docs/images/seconds_light.png" alt="The seconds dial (light)"></td>
<td width="50%"><img src="docs/images/seconds_dark.png" alt="The seconds dial (dark)"></td>
</tr></table>

## Bounds

```php
TimePicker::make('pickup')
    ->minTime('09:00')
    ->maxTime('17:00');
```

Numerals outside the range are dimmed and refuse the pointer, typing is pulled back inside, and the same bound is checked again on the server — the panel is not the only way a value arrives. The message names the bound in the field's own format ("must be no earlier than 9:00 AM"). Both accept a string, a `Carbon` instance, or a closure.

<table><tr>
<td width="50%"><img src="docs/images/bounds_light.png" alt="Hours outside 9 AM–5 PM dimmed (light)"></td>
<td width="50%"><img src="docs/images/bounds_dark.png" alt="Hours outside 9 AM–5 PM dimmed (dark)"></td>
</tr></table>

## Date and time

`DateTimePicker` pairs the same clock with a calendar. Picking a day moves straight to the clock, and the switch at the top of the panel moves between the two.

```php
DateTimePicker::make('ships_at')
    ->seconds(false)
    ->displayFormat('M j, Y g:i A')
    ->minDate(now()->startOfDay())
    ->maxDate(now()->addMonths(3))
    ->disabledDates(['2026-12-25', '2026-12-26'])
    ->minTime('08:00')
    ->maxTime('20:00');
```

<table><tr>
<td width="50%"><img src="docs/images/date_time_calendar_light.png" alt="The calendar (light)"></td>
<td width="50%"><img src="docs/images/date_time_calendar_dark.png" alt="The calendar (dark)"></td>
</tr></table>

<table><tr>
<td width="50%"><img src="docs/images/date_time_clock_light.png" alt="The clock after picking a day (light)"></td>
<td width="50%"><img src="docs/images/date_time_clock_dark.png" alt="The clock after picking a day (dark)"></td>
</tr></table>

`minDate()` and `maxDate()` disable the days outside them, and on the first and last day their times narrow the clock too. `minTime()` / `maxTime()` stay a daily window, applied on every day. Month and weekday names follow the field's locale.

Filament's other date options apply as they do there:

```php
DateTimePicker::make('ships_at')
    ->weekStartsOnSunday()                  // or firstDayOfWeek(0–7)
    ->defaultFocusedDate(now()->addWeek())  // the month an empty field opens on
    ->closeOnDateSelection();               // pick a day and close, without the clock
```

Without a time, it is a calendar only:

```php
DateTimePicker::make('delivered_on')->time(false);
```

## Typing and the keyboard

The big digits in the panel are inputs. Click one and type; it moves to the minute on its own when the hour cannot be extended. The arrow keys nudge by the field's own step, Escape closes the panel, and Backspace on the closed field clears it.

## Closing and the footer

By default the panel waits for **OK**. To save and close as soon as the last dial is set (the minute, or the second when seconds are on), and hide OK:

```php
TimePicker::make('call_back_at')->closeOnComplete();
```

The footer's buttons are Filament's own. Relabel them, or drop the "Now" shortcut:

```php
TimePicker::make('deliver_at')
    ->okLabel('Set time')
    ->cancelLabel('Back')
    ->nowLabel('Right now')
    ->nowButton(false);
```

## On phones

`native()` hands rendering back to Filament, which gives the browser's own control — on a phone the platform's picker is the better choice:

```php
TimePicker::make('deliver_at')
    ->native(fn (): bool => str_contains((string) request()->userAgent(), 'Mobile'));
```

## Configuration

`config/time-picker.php` sets the defaults every field starts from:

```bash
php artisan vendor:publish --tag=devletes-time-picker-config
```

```php
return [
    'hours_cycle' => null,         // 12, 24, or null to read it off each field's display format
    'close_on_complete' => false,  // save and close once the last dial is set
    'now_button' => true,          // show the "Now" shortcut
];
```

## Theming

Every colour resolves through Filament's palette variables, so the picker follows your panel's primary colour and its light and dark themes without being told. To change it further, override the `--fi-dtp-*` variables in your theme stylesheet:

```css
html .fi-dtp {
    --fi-dtp-accent: var(--success-600);
    --fi-dtp-plate: var(--gray-50);
}

html.dark .fi-dtp {
    --fi-dtp-accent: var(--success-400);
}
```

The variables are `surface`, `plate` (the dial), `border`, `text`, `muted`, `faded`, `accent`, `on-accent` and `hover`.

Or publish the views to change the markup:

```bash
php artisan vendor:publish --tag=devletes-time-picker-views
```

## Translations

Every user-facing string goes through `devletes-time-picker::time-picker.*`. Publish the language files to reword them or add a locale:

```bash
php artisan vendor:publish --tag=devletes-time-picker-translations
```

Then edit or add files under `lang/vendor/devletes-time-picker/`.

## API reference

Everything Filament's `TimePicker` and `DateTimePicker` offer, plus:

| Method | What it does |
| --- | --- |
| `hoursCycle(12\|24)` | Fixes the face rather than reading it off the display format |
| `minTime($time)` | The earliest time of day the field will take. String, `Carbon`, or closure |
| `maxTime($time)` | The latest time of day the field will take. String, `Carbon`, or closure |
| `closeOnComplete()` | Save and close once the last dial is set, and hide OK |
| `nowButton(false)` | Drops the "Now" shortcut from the footer |
| `okLabel($label)` | Relabels the confirm button |
| `cancelLabel($label)` | Relabels the cancel button |
| `nowLabel($label)` | Relabels the "Now" shortcut |
| `native()` | Hands rendering back to Filament for the browser's own control |

On `DateTimePicker`, Filament's `minDate()`, `maxDate()`, `disabledDates()`, `firstDayOfWeek()`, `weekStartsOnMonday()`, `weekStartsOnSunday()`, `defaultFocusedDate()`, `closeOnDateSelection()`, `time()` and `seconds()` all apply to the calendar and the clock.

## Testing

```bash
composer install
composer test
```

The suite runs on Testbench with the real Filament stack, against PHP 8.2–8.4 on Laravel 12 and 13.

## Need something custom?

We build production Filament panels and plugins for teams that want to ship fast without compromising on polish. If you need a custom feature, an extended variant of this package, or a fully bespoke component built for your stack, we can help.

- **Browse the rest of our Filament work:** [filament.devletes.com](https://filament.devletes.com)
- **Get in touch:** [salman@devletes.com](mailto:salman@devletes.com)

Typical engagements: new Filament plugins, custom resources/widgets/actions, theme + UX work, integrations with your existing services, and one-off tailored forks of our open-source packages.

## Have you seen our Orbit theme?

Orbit is our dark-first theme for Filament 5. Every panel surface is restyled — sidebar, topbar, forms, tables, modals, notifications.

<table><tr>
<td width="50%"><img src="https://filament.devletes.com/screenshots/orbit/resource_detail_with_hero_card_light.png" alt="Orbit theme, light mode"></td>
<td width="50%"><img src="https://filament.devletes.com/screenshots/orbit/resource_detail_with_hero_card.png" alt="Orbit theme, dark mode"></td>
</tr></table>

**[Check it out here](https://filament.devletes.com/orbit) →**

## Credits

- [Salman Hijazi](https://www.linkedin.com/in/syedsalmanhijazi/)

## License

MIT. See [LICENSE.md](LICENSE.md).
