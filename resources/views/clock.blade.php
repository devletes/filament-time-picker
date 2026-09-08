@php
    $config = $getClockConfig();
    $face = $getClockFace();
    $statePath = $getStatePath();
    $id = $getId();
    $livewireKey = $getLivewireKey();
    $isDisabled = $config['isDisabled'];
    $isReadOnly = $config['isReadOnly'];
    $isTwelveHour = $config['hoursCycle'] === 12;
    $hasSeconds = $config['hasSeconds'];

    // The panel is built server-side, so it has to be rebuilt when the shape
    // of it moves — a different cycle, seconds arriving, new bounds.
    $shapeKey = substr(md5(serialize([
        $config['hoursCycle'], $hasSeconds, $isDisabled, $isReadOnly,
        $config['minTime'], $config['maxTime'], $config['minutesStep'],
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
            prefix-icon="heroicon-m-clock"
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
                class="fi-dtp-panel"
            >
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

                <div class="fi-dtp-footer">
                    @if ($hasNowButton())
                        <button type="button" x-on:click="setNow()" class="fi-dtp-action fi-dtp-action-ghost">
                            {{ $getNowLabel() }}
                        </button>
                    @endif

                    <div class="fi-dtp-footer-end">
                        <button type="button" x-on:click="cancel()" class="fi-dtp-action fi-dtp-action-ghost">
                            {{ $getCancelLabel() }}
                        </button>

                        @unless ($config['closeOnComplete'])
                            <button type="button" x-on:click="confirm()" class="fi-dtp-action fi-dtp-action-primary">
                                {{ $getOkLabel() }}
                            </button>
                        @endunless
                    </div>
                </div>
            </div>
        @endunless
    </div>
</x-dynamic-component>
