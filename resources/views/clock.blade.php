@php
    $config = $getClockConfig();
    $face = $getClockFace();
    $statePath = $getStatePath();
    $id = $getId();
    $livewireKey = $getLivewireKey();
    $isDisabled = $config['isDisabled'];
    $isReadOnly = $config['isReadOnly'];
    $hasDate = $config['hasDate'];
    $hasTime = $config['hasTime'];
    $isTwelveHour = $config['hoursCycle'] === 12;
    $hasSeconds = $config['hasSeconds'];

    // The panel is built server-side, so it is rebuilt whenever its shape changes.
    $shapeKey = substr(md5(serialize([
        $config['hoursCycle'], $hasSeconds, $hasDate, $hasTime, $isDisabled, $isReadOnly,
        $config['minTime'], $config['maxTime'], $config['minutesStep'],
        $config['minDate'], $config['maxDate'], $config['disabledDates'],
    ])), 0, 16);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="devletesTimePicker({
            config: @js($config),
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
        })"
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ $shapeKey }}"
        x-on:keydown.esc="open && ($event.stopPropagation(), close())"
        {{ $getExtraAlpineAttributeBag()->class(['fi-dtp']) }}
    >
        <x-filament::input.wrapper
            :disabled="$isDisabled"
            :valid="! $errors->has($statePath)"
            :prefix-icon="$hasDate ? 'heroicon-m-calendar-days' : 'heroicon-m-clock'"
            prefix-icon-color="gray"
            x-bind:class="{ 'fi-dtp-open': open }"
        >
            <button
                type="button"
                x-ref="trigger"
                x-on:click="toggle()"
                x-on:keydown.enter.prevent.stop="toggle()"
                x-on:keydown.backspace.prevent.stop="clear()"
                x-on:keydown.delete.prevent.stop="clear()"
                x-bind:aria-expanded="open ? 'true' : 'false'"
                aria-haspopup="dialog"
                @disabled($isDisabled || $isReadOnly)
                @if ($isAutofocused()) autofocus @endif
                class="fi-dtp-trigger"
            >
                <input
                    @disabled($isDisabled)
                    readonly
                    tabindex="-1"
                    @if ($id) id="{{ $id }}" @endif
                    placeholder="{{ $getPlaceholder() }}"
                    x-model="displayText"
                    class="fi-input fi-dtp-display"
                />
            </button>
        </x-filament::input.wrapper>

        @unless ($isDisabled || $isReadOnly)
            <div
                x-ref="panel"
                x-cloak
                x-show="open"
                x-float.placement.bottom-start.offset.flip.shift="{ offset: 8 }"
                x-on:click.outside="close()"
                role="dialog"
                aria-modal="false"
                @class(['fi-dtp-panel', 'fi-dtp-panel-with-date' => $hasDate])
            >
                @if ($hasDate && $hasTime)
                    <div class="fi-dtp-views" role="tablist">
                        <button
                            type="button"
                            role="tab"
                            x-on:click="view = 'date'"
                            x-bind:aria-selected="view === 'date' ? 'true' : 'false'"
                            x-bind:class="{ 'fi-dtp-view-active': view === 'date' }"
                            class="fi-dtp-view"
                        >
                            <x-filament::icon icon="heroicon-m-calendar-days" class="fi-dtp-view-icon" />
                            <span x-text="dateLabel || @js($config['labels']['date'])"></span>
                        </button>

                        <button
                            type="button"
                            role="tab"
                            x-on:click="showTime()"
                            x-bind:aria-selected="view === 'time' ? 'true' : 'false'"
                            x-bind:class="{ 'fi-dtp-view-active': view === 'time' }"
                            class="fi-dtp-view"
                        >
                            <x-filament::icon icon="heroicon-m-clock" class="fi-dtp-view-icon" />
                            <span x-text="timeLabel || @js($config['labels']['time'])"></span>
                        </button>
                    </div>
                @endif

                @if ($hasDate)
                    <div x-show="view === 'date'" class="fi-dtp-calendar">
                        <div class="fi-dtp-calendar-header">
                            <button type="button" x-on:click="previousMonth()" aria-label="{{ __('devletes-time-picker::time-picker.previous_month') }}" class="fi-dtp-calendar-nav">
                                <x-filament::icon icon="heroicon-m-chevron-left" class="fi-dtp-calendar-nav-icon" />
                            </button>

                            <x-filament::dropdown placement="bottom-start" max-height="16rem" class="fi-dtp-calendar-month-dropdown">
                                <x-slot name="trigger">
                                    <button type="button" aria-label="{{ __('devletes-time-picker::time-picker.month') }}" class="fi-dtp-calendar-month">
                                        <span x-text="monthNames[focusedMonth]"></span>
                                        <x-filament::icon icon="heroicon-m-chevron-down" class="fi-dtp-calendar-month-icon" />
                                    </button>
                                </x-slot>

                                <x-filament::dropdown.list>
                                    <template x-for="(name, index) in monthNames" x-bind:key="index">
                                        <x-filament::dropdown.list.item
                                            x-on:click="focusedMonth = index"
                                            x-bind:class="{ 'fi-dtp-calendar-month-current': index === focusedMonth }"
                                        >
                                            <span x-text="name"></span>
                                        </x-filament::dropdown.list.item>
                                    </template>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>

                            <input type="number" inputmode="numeric" x-model.number.debounce="focusedYear" aria-label="{{ __('devletes-time-picker::time-picker.year') }}" class="fi-dtp-calendar-year" />

                            <button type="button" x-on:click="nextMonth()" aria-label="{{ __('devletes-time-picker::time-picker.next_month') }}" class="fi-dtp-calendar-nav">
                                <x-filament::icon icon="heroicon-m-chevron-right" class="fi-dtp-calendar-nav-icon" />
                            </button>
                        </div>

                        <div class="fi-dtp-calendar-weekdays">
                            <template x-for="(name, index) in dayNames" x-bind:key="index">
                                <div x-text="name" class="fi-dtp-calendar-weekday"></div>
                            </template>
                        </div>

                        <div role="grid" class="fi-dtp-calendar-days">
                            <template x-for="blank in leadingBlanks" x-bind:key="'blank-' + blank">
                                <div></div>
                            </template>

                            {{-- Not `day`: Alpine would resolve this.day in the component to the loop variable. --}}
                            <template x-for="calendarDay in daysInFocusedMonth" x-bind:key="calendarDay">
                                <div
                                    x-text="calendarDay"
                                    x-on:click="selectDay(calendarDay)"
                                    role="option"
                                    x-bind:aria-selected="dayIsSelected(calendarDay) ? 'true' : 'false'"
                                    x-bind:class="{
                                        'fi-dtp-calendar-day-today': dayIsToday(calendarDay),
                                        'fi-dtp-calendar-day-selected': dayIsSelected(calendarDay),
                                        'fi-dtp-calendar-day-disabled': dayIsDisabled(calendarDay),
                                    }"
                                    class="fi-dtp-calendar-day"
                                ></div>
                            </template>
                        </div>
                    </div>
                @endif

                @if ($hasTime)
                    <div x-show="view === 'time'" class="fi-dtp-time">
                        <div class="fi-dtp-readout">
                            <input
                                x-ref="hourInput"
                                type="text"
                                inputmode="numeric"
                                maxlength="2"
                                autocomplete="off"
                                aria-label="{{ $config['labels']['hour'] }}"
                                x-model="hourText"
                                x-on:focus="stage = 'hour'; $event.target.select()"
                                x-on:input="onTyped('hour', $event)"
                                x-on:blur="commitTyped('hour')"
                                x-on:keydown.up.prevent="nudge(1)"
                                x-on:keydown.down.prevent="nudge(-1)"
                                x-bind:class="{ 'fi-dtp-readout-active': stage === 'hour' }"
                                class="fi-dtp-readout-input"
                            />

                            <span class="fi-dtp-readout-separator">:</span>

                            <input
                                x-ref="minuteInput"
                                type="text"
                                inputmode="numeric"
                                maxlength="2"
                                autocomplete="off"
                                aria-label="{{ $config['labels']['minute'] }}"
                                x-model="minuteText"
                                x-on:focus="stage = 'minute'; $event.target.select()"
                                x-on:input="onTyped('minute', $event)"
                                x-on:blur="commitTyped('minute')"
                                x-on:keydown.up.prevent="nudge(1)"
                                x-on:keydown.down.prevent="nudge(-1)"
                                x-bind:class="{ 'fi-dtp-readout-active': stage === 'minute' }"
                                class="fi-dtp-readout-input"
                            />

                            @if ($hasSeconds)
                                <span class="fi-dtp-readout-separator">:</span>

                                <input
                                    x-ref="secondInput"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="2"
                                    autocomplete="off"
                                    aria-label="{{ $config['labels']['second'] }}"
                                    x-model="secondText"
                                    x-on:focus="stage = 'second'; $event.target.select()"
                                    x-on:input="onTyped('second', $event)"
                                    x-on:blur="commitTyped('second')"
                                    x-on:keydown.up.prevent="nudge(1)"
                                    x-on:keydown.down.prevent="nudge(-1)"
                                    x-bind:class="{ 'fi-dtp-readout-active': stage === 'second' }"
                                    class="fi-dtp-readout-input"
                                />
                            @endif

                            @if ($isTwelveHour)
                                <div class="fi-dtp-meridiem" role="group">
                                    @foreach (['am', 'pm'] as $meridiem)
                                        <button
                                            type="button"
                                            x-on:click="setMeridiem('{{ $meridiem }}')"
                                            x-bind:class="{ 'fi-dtp-meridiem-active': meridiem === '{{ $meridiem }}' }"
                                            x-bind:disabled="! canUseMeridiem('{{ $meridiem }}')"
                                            class="fi-dtp-meridiem-option"
                                        >{{ $config['labels']['meridiem'][$meridiem] }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="fi-dtp-dial">
                            <svg
                                x-ref="face"
                                viewBox="0 0 224 224"
                                class="fi-dtp-face"
                                x-on:pointerdown="onPointerDown($event)"
                                x-on:pointermove="onPointerMove($event)"
                                x-on:pointerup="onPointerUp($event)"
                                x-on:pointercancel="onPointerUp($event)"
                                x-bind:aria-label="stageLabel"
                            >
                                <circle cx="112" cy="112" r="110" class="fi-dtp-face-plate" />

                                <g x-show="hasSelection" class="fi-dtp-hand">
                                    <line
                                        x1="112"
                                        y1="112"
                                        x2="112"
                                        x-bind:y2="112 - handLength"
                                        x-bind:transform="`rotate(${handAngle} 112 112)`"
                                        class="fi-dtp-hand-line"
                                    />
                                    <circle
                                        cx="112"
                                        x-bind:cy="112 - handLength"
                                        r="17"
                                        x-bind:transform="`rotate(${handAngle} 112 112)`"
                                        class="fi-dtp-hand-knob"
                                    />
                                </g>

                                <circle cx="112" cy="112" r="4" class="fi-dtp-hand-pin" />

                                <text x="112" y="96" text-anchor="middle" dominant-baseline="central" x-text="config.labels.dial[stage]" class="fi-dtp-dial-label"></text>

                                @foreach ($face as $stage => $rings)
                                    @if ($stage === 'second' && ! $hasSeconds)
                                        @continue
                                    @endif

                                    <g x-show="stage === '{{ $stage }}'">
                                        @foreach ($rings as $ring)
                                            @foreach ($ring as $numeral)
                                                <text
                                                    x="{{ $numeral['x'] }}"
                                                    y="{{ $numeral['y'] }}"
                                                    text-anchor="middle"
                                                    dominant-baseline="central"
                                                    x-bind:class="numeralClass('{{ $stage }}', {{ $numeral['value'] }})"
                                                    @class([
                                                        'fi-dtp-numeral',
                                                        'fi-dtp-numeral-inner' => ! $numeral['outer'],
                                                    ])
                                                >{{ $numeral['label'] }}</text>
                                            @endforeach
                                        @endforeach
                                    </g>
                                @endforeach
                            </svg>
                        </div>
                    </div>
                @endif

                <div class="fi-dtp-footer">
                    @if ($hasNowButton())
                        <x-filament::button type="button" color="gray" size="sm" x-on:click="setNow()">
                            {{ $getNowLabel() }}
                        </x-filament::button>
                    @endif

                    <div class="fi-dtp-footer-end">
                        <x-filament::button type="button" color="gray" size="sm" x-on:click="cancel()">
                            {{ $getCancelLabel() }}
                        </x-filament::button>

                        @unless ($config['closeOnComplete'])
                            <x-filament::button type="button" size="sm" x-on:click="confirm()">
                                {{ $getOkLabel() }}
                            </x-filament::button>
                        @endunless
                    </div>
                </div>
            </div>
        @endunless
    </div>
</x-dynamic-component>
