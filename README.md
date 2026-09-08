# Filament Time Picker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devletes/filament-time-picker.svg?style=flat-square)](https://packagist.org/packages/devletes/filament-time-picker)
[![Total Downloads](https://img.shields.io/packagist/dt/devletes/filament-time-picker.svg?style=flat-square)](https://packagist.org/packages/devletes/filament-time-picker)
[![License](https://img.shields.io/packagist/l/devletes/filament-time-picker.svg?style=flat-square)](https://packagist.org/packages/devletes/filament-time-picker)
[![GitHub Stars](https://img.shields.io/github/stars/devletes/filament-time-picker?style=flat-square)](https://github.com/devletes/filament-time-picker/stargazers)

A clock face for Filament's time fields — hour and minute dials, drag or tap or type.

- **A drop-in replacement.** Extends Filament's own `TimePicker`, so swapping the import is the whole migration
- **No dependencies.** No jQuery, no date library, no build step — vanilla JavaScript on the Alpine you already have
- **The dial follows the field.** `g:i A` gets a 12-hour face with AM/PM, `H:i` gets a 24-hour face with an inner ring
- **Every minute is reachable**, not every fifth, and `->minutesStep()` still coarsens it when you want that
- **Bounds that hold.** `->minTime()` / `->maxTime()` dim the dial, clamp typing, and are re-checked server-side
- **Type instead.** The panel's digits are inputs, with arrow keys stepping by the field's own step
- **Optional seconds**, a third dial when `->seconds()` is on
- Themed off Filament's palette variables — follows your panel's colour, light and dark
- Translatable, publishable config and views

## Requirements

- PHP `^8.2`
- Filament `^4.0` or `^5.0`

## Installation

```bash
composer require devletes/filament-time-picker
```

That is all. The stylesheet and the script register themselves with Filament's asset manager, so they are served on the panels that need them — there is nothing to add to your custom Tailwind theme.

Publish anything you want to change:

```bash
php artisan vendor:publish --tag=devletes-time-picker-config
php artisan vendor:publish --tag=devletes-time-picker-views
php artisan vendor:publish --tag=devletes-time-picker-translations
```

## Quick start

```php
use Devletes\TimePicker\Forms\Components\TimePicker;

TimePicker::make('clocked_in_at')
    ->label('Clocked in at')
    ->seconds(false)
    ->displayFormat('g:i A');
```

## Why

Filament's own `TimePicker` gives you hour and minute spinners. That is fine for a field nobody dwells on, and awkward for one people set all day — a shift, a schedule, an attendance correction.

The other clock-face picker on the plugin list reaches for a jQuery plugin from 2017, and loads jQuery from a CDN on every page of your panel to get there. This one is about four hundred lines of vanilla JavaScript and takes nothing with it.

## A drop-in replacement

The component **extends** `Filament\Forms\Components\TimePicker`, so swapping the import is the whole migration:

```diff
-use Filament\Forms\Components\TimePicker;
+use Devletes\TimePicker\Forms\Components\TimePicker;
```

Everything you already know still works and still means the same thing — `->seconds()`, `->minutesStep()`, `->format()`, `->displayFormat()`, `->timezone()`, `->live()`, `->disabled()`, the affixes, the validation. The state cast is Filament's own, so nothing about what is stored changes.

## The dial follows the field

Say nothing, and the cycle is read off the display format. A field that writes its hour as 1–12 gets the 12-hour face with an AM/PM switch; anything else gets the 24-hour face, with 13–23 and midnight on an inner ring.

```php
TimePicker::make('start')->displayFormat('g:i A');   // 12-hour face
TimePicker::make('start')->displayFormat('H:i');     // 24-hour face
TimePicker::make('start')->hoursCycle(24);           // say so yourself
```

The alternative — a fixed default — leaves a dial reading "3" beside an input reading "15:00". Set `time-picker.hours_cycle` in the config to make one cycle the rule everywhere.

## Precision

The dial takes **every minute**, not every fifth. The numerals are drawn at the fives because sixty of them is unreadable, but dragging between them lands on 9:07 the way you would expect.

Filament's own stepping is honoured if you want it coarser:

```php
TimePicker::make('slot')->minutesStep(15);   // quarter hours only
```

`->seconds()` adds a third dial, and `->secondsStep()` and `->hoursStep()` work the same way.

## Bounds

```php
TimePicker::make('clocked_in_at')
    ->minTime('06:00')
    ->maxTime('22:00');
```

Numerals outside the range are dimmed and refuse the pointer, typing is pulled back inside, and the same bound is checked again on the server — the panel is not the only way a value arrives. The message names the bound in the field's own format ("must be no earlier than 6:00 AM").

Both accept a string, a `Carbon` instance, or a closure.

## Typing

The two big digits in the panel are inputs. Click one and type; it moves to the minute on its own when the hour cannot be extended. The arrow keys nudge by the field's own step. Backspace on the closed field clears it.

## API reference

Everything Filament's `TimePicker` offers, plus:

| Method | What it does |
| --- | --- |
| `->hoursCycle(12\|24)` | Fixes the face rather than reading it off the display format. |
| `->minTime($time)` | The earliest value the field will take. String, `Carbon`, or closure. |
| `->maxTime($time)` | The latest value the field will take. String, `Carbon`, or closure. |
| `->closeOnComplete()` | Commit and close on the last dial instead of waiting for OK. |
| `->nowButton(false)` | Drops the "Now" shortcut from the footer. |
| `->okLabel($label)` | Relabels the confirm button. |
| `->cancelLabel($label)` | Relabels the cancel button. |
| `->nowLabel($label)` | Relabels the "Now" shortcut. |
| `->native()` | Hands rendering back to Filament, giving the browser's own control. |

`->native()` is worth knowing about: on a phone the platform's own time control is better than anything a web page can draw, so it is a reasonable thing to switch on for small screens.

## Configuration

`config/time-picker.php` sets the defaults every field starts from:

```php
return [
    // 12, 24, or null to read the cycle off each field's display format.
    'hours_cycle' => null,

    // Commit and close once the last dial is set.
    'close_on_complete' => false,

    // Show the "Now" shortcut in the panel footer.
    'now_button' => true,
];
```

## Theming

Every colour resolves through Filament's palette variables — `--primary-600`, `--gray-200` and friends — so the picker follows your panel's colour and its light and dark themes without being told.

If you want to go further, publish the views, or override the `.fi-dtp-*` classes in your theme's stylesheet.

## Translations

All user-facing strings go through `trans('devletes-time-picker::time-picker.*')`. To customise them or add locales, publish the language files:

```bash
php artisan vendor:publish --tag=devletes-time-picker-translations
```

Then edit or add files under `lang/vendor/devletes-time-picker/`.

## Testing

```bash
composer test
```

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
