/**
 * Devletes Time Picker — a clock face for Filament. (rev 1)
 *
 * No dependencies: the formatting and parsing this needs is a handful of PHP
 * date tokens, which is far less code than a date library would cost. The
 * picker edits a draft while it is open and writes to Livewire only when the
 * panel is confirmed, so a cancel really does leave the field as it was.
 *
 * Everything is held in 24-hour internally. AM/PM is a presentation of the
 * same hour, which is what stops noon and midnight going wrong.
 */

// Everything lives inside this wrapper. Filament serves package scripts as
// classic scripts, which share one global lexical scope, so a bare top-level
// `const clamp` here would collide with another package's and take the whole
// file down with a redeclaration error before Alpine ever sees it.
;(() => {
    /** How far out the two rings of numerals sit, in the face's own units. */
    const OUTER_RADIUS = 84
    const INNER_RADIUS = 54
    /** Anything inside this belongs to the inner ring on a 24-hour face. */
    const RING_BOUNDARY = 69
    const FACE_SIZE = 224
    const FACE_CENTRE = FACE_SIZE / 2


    const pad2 = (value) => String(value).padStart(2, '0')

    const clamp = (value, min, max) => Math.min(Math.max(value, min), max)

    /** Seconds since midnight, the one currency all the bounds maths uses. */
    const toSeconds = (hour, minute, second) => (hour * 3600) + (minute * 60) + second

    const parseBound = (value) => {
        if (! value) {
            return null
        }

        const parts = String(value).split(':').map((part) => parseInt(part, 10))

        if (parts.some(Number.isNaN)) {
            return null
        }

        return toSeconds(parts[0] ?? 0, parts[1] ?? 0, parts[2] ?? 0)
    }

    /**
     * The PHP date tokens a value can be written with. Anything else in the
     * format is passed through, and a backslash escapes the character after it.
     * The date tokens are here because Filament's internal state for a
     * non-native picker is a whole `Y-m-d H:i:s`, even for a time-only field.
     */
    const formatValue = ({ year, month, day, hour, minute, second }, format) => {
        const meridiem = hour < 12 ? 'AM' : 'PM'
        const twelve = hour % 12 === 0 ? 12 : hour % 12

        let out = ''

        for (let i = 0; i < format.length; i++) {
            const token = format[i]

            if (token === '\\') {
                out += format[i + 1] ?? ''
                i++

                continue
            }

            switch (token) {
                case 'H': out += pad2(hour); break
                case 'G': out += String(hour); break
                case 'h': out += pad2(twelve); break
                case 'g': out += String(twelve); break
                case 'i': out += pad2(minute); break
                case 's': out += pad2(second); break
                case 'A': out += meridiem; break
                case 'a': out += meridiem.toLowerCase(); break
                case 'Y': out += String(year); break
                case 'y': out += pad2(year % 100); break
                case 'm': out += pad2(month); break
                case 'n': out += String(month); break
                case 'd': out += pad2(day); break
                case 'j': out += String(day); break
                default: out += token
            }
        }

        return out
    }

    /**
     * Read a stored value back. Filament hands a non-native picker a whole
     * `Y-m-d H:i:s`, so the time is found by its shape rather than by taking
     * the first numbers in the string — "2026-09-07 18:41:00" starts with a
     * year, and reading that as the hour is how a picker ends up on 8:26.
     */
    const parseValue = (value) => {
        if (typeof value !== 'string' || value.trim() === '') {
            return null
        }

        const time = value.match(/(\d{1,2}):(\d{2})(?::(\d{2}))?/)

        if (! time) {
            return null
        }

        let hour = parseInt(time[1], 10)
        const minute = parseInt(time[2], 10)
        const second = parseInt(time[3] ?? '0', 10)

        const meridiem = value.slice(time.index).toLowerCase().match(/([ap])\.?m/)

        if (meridiem) {
            hour = (hour % 12) + (meridiem[1] === 'p' ? 12 : 0)
        }

        if (hour > 23 || minute > 59 || second > 59) {
            return null
        }

        const date = value.match(/(\d{4})-(\d{2})-(\d{2})/)
        const today = new Date()

        return {
            year: date ? parseInt(date[1], 10) : today.getFullYear(),
            month: date ? parseInt(date[2], 10) : today.getMonth() + 1,
            day: date ? parseInt(date[3], 10) : today.getDate(),
            hour,
            minute,
            second,
        }
    }

    /** The date a written value keeps: the one it already had, else today. */
    const dateOf = (value) => {
        const parsed = parseValue(value)

        if (parsed) {
            return { year: parsed.year, month: parsed.month, day: parsed.day }
        }

        const today = new Date()

        return { year: today.getFullYear(), month: today.getMonth() + 1, day: today.getDate() }
    }

    document.addEventListener('alpine:init', () => {
        window.Alpine.data('devletesTimePicker', ({ config, state }) => ({
            config,
            state,

            open: false,
            stage: 'hour',

            hour: null,
            minute: null,
            second: null,

            hourText: '',
            minuteText: '',
            secondText: '',

            displayText: '',

            dragging: false,

            init() {
                this.syncFromState()

                // The server can change the field underneath us — another action
                // writing to it, a form reset — and the panel has to follow.
                this.$watch('state', () => {
                    if (! this.open) {
                        this.syncFromState()
                    }
                })

                if (this.config.isAutofocused) {
                    this.$nextTick(() => this.$refs.trigger?.focus())
                }
            },

            // ------------------------------------------------------------------
            // Reading and writing the field
            // ------------------------------------------------------------------

            syncFromState() {
                const parsed = parseValue(this.state)

                this.hour = parsed?.hour ?? null
                this.minute = parsed?.minute ?? null
                this.second = parsed?.second ?? null
                this.stage = 'hour'

                this.syncText()
                this.refreshDisplay()
            },

            refreshDisplay() {
                const parsed = parseValue(this.state)

                this.displayText = parsed ? formatValue(parsed, this.config.displayFormat) : ''
            },

            /**
             * The draft as Filament stores it, or null while it is incomplete.
             * The date is carried through untouched: the field is about a time,
             * but the state it lives in is a whole datetime.
             */
            get draftValue() {
                if (this.hour === null) {
                    return null
                }

                return formatValue({
                    ...dateOf(this.state),
                    hour: this.hour,
                    minute: this.minute ?? 0,
                    second: this.second ?? 0,
                }, this.config.internalFormat)
            },

            commit() {
                const value = this.draftValue

                if (value === null) {
                    return
                }

                this.state = value
                this.refreshDisplay()
            },

            clear() {
                this.state = null
                this.syncFromState()
            },

            // ------------------------------------------------------------------
            // The panel
            // ------------------------------------------------------------------

            toggle() {
                this.open ? this.close() : this.show()
            },

            show() {
                this.syncFromState()
                this.open = true
            },

            close() {
                this.open = false
                this.dragging = false
                this.$refs.trigger?.focus()
            },

            cancel() {
                this.syncFromState()
                this.open = false
                this.$refs.trigger?.focus()
            },

            confirm() {
                this.commit()
                this.close()
            },

            setNow() {
                const now = new Date()

                this.hour = now.getHours()
                this.minute = this.snap(now.getMinutes(), this.config.minutesStep, 60)
                this.second = this.config.hasSeconds
                    ? this.snap(now.getSeconds(), this.config.secondsStep, 60)
                    : 0

                this.pullIntoBounds()
                this.syncText()

                if (this.config.closeOnComplete) {
                    this.confirm()
                }
            },

            get stageLabel() {
                const labels = this.config.labels

                return {
                    hour: labels.selectHour,
                    minute: labels.selectMinute,
                    second: labels.selectSecond,
                }[this.stage]
            },

            // ------------------------------------------------------------------
            // The hand
            // ------------------------------------------------------------------

            get hasSelection() {
                return this.currentStageValue !== null
            },

            get currentStageValue() {
                return { hour: this.hour, minute: this.minute, second: this.second }[this.stage]
            },

            get handAngle() {
                const value = this.currentStageValue

                if (value === null) {
                    return 0
                }

                if (this.stage !== 'hour') {
                    return value * 6
                }

                return (value % 12) * 30
            },

            get handLength() {
                return this.stage === 'hour' && this.isInnerHour(this.hour)
                    ? INNER_RADIUS
                    : OUTER_RADIUS
            },

            /** Midnight and the afternoon hours live on the inner ring. */
            isInnerHour(hour) {
                return this.config.hoursCycle === 24 && (hour === 0 || hour >= 13)
            },

            // ------------------------------------------------------------------
            // Numerals
            // ------------------------------------------------------------------

            numeralClass(stage, value) {
                return {
                    'fi-dtp-numeral-selected': this.isNumeralSelected(stage, value),
                    'fi-dtp-numeral-disabled': ! this.isNumeralAllowed(stage, value),
                }
            },

            isNumeralSelected(stage, value) {
                if (stage === 'minute') {
                    return this.minute === value
                }

                if (stage === 'second') {
                    return this.second === value
                }

                if (this.hour === null) {
                    return false
                }

                return this.config.hoursCycle === 24
                    ? this.hour === value
                    : this.hour % 12 === value % 12
            },

            /**
             * A numeral is offered when any time it could still lead to is inside
             * the bounds: an hour whose minutes are all out of range is not worth
             * pointing at, but one with a single reachable minute is.
             */
            isNumeralAllowed(stage, value) {
                const min = parseBound(this.config.minTime)
                const max = parseBound(this.config.maxTime)

                if (min === null && max === null) {
                    return true
                }

                let from
                let to

                if (stage === 'hour') {
                    const hour = this.hourFromNumeral(value, this.config.hoursCycle === 24 && this.isInnerNumeral(value))

                    from = toSeconds(hour, 0, 0)
                    to = toSeconds(hour, 59, 59)
                } else if (stage === 'minute') {
                    const hour = this.hour ?? 0

                    from = toSeconds(hour, value, 0)
                    to = toSeconds(hour, value, 59)
                } else {
                    const point = toSeconds(this.hour ?? 0, this.minute ?? 0, value)

                    from = point
                    to = point
                }

                if (min !== null && to < min) {
                    return false
                }

                return ! (max !== null && from > max)
            },

            /** Only the 24-hour face has a second ring, and 0 and 13–23 are on it. */
            isInnerNumeral(value) {
                return value === 0 || value >= 13
            },

            canUseMeridiem(meridiem) {
                const offset = meridiem === 'pm' ? 12 : 0

                for (let hour = offset; hour < offset + 12; hour++) {
                    if (this.isNumeralAllowed('hour', this.config.hoursCycle === 24 ? hour : (hour % 12 || 12))) {
                        if (this.config.hoursCycle === 24) {
                            return true
                        }

                        // On a 12-hour face the numeral does not say which half of
                        // the day it is, so the bound has to be checked on the
                        // hour the switch would actually produce.
                        const min = parseBound(this.config.minTime)
                        const max = parseBound(this.config.maxTime)
                        const from = toSeconds(hour, 0, 0)
                        const to = toSeconds(hour, 59, 59)

                        if ((min === null || to >= min) && (max === null || from <= max)) {
                            return true
                        }
                    }
                }

                return false
            },

            // ------------------------------------------------------------------
            // Meridiem
            // ------------------------------------------------------------------

            get meridiem() {
                if (this.hour === null) {
                    return 'am'
                }

                return this.hour < 12 ? 'am' : 'pm'
            },

            setMeridiem(meridiem) {
                if (this.hour === null) {
                    this.hour = meridiem === 'pm' ? 12 : 0
                } else if (meridiem === 'pm' && this.hour < 12) {
                    this.hour += 12
                } else if (meridiem === 'am' && this.hour >= 12) {
                    this.hour -= 12
                }

                this.pullIntoBounds()
                this.syncText()
            },

            // ------------------------------------------------------------------
            // Pointer
            // ------------------------------------------------------------------

            onPointerDown(event) {
                if (event.button !== undefined && event.button !== 0) {
                    return
                }

                this.dragging = true

                try {
                    this.$refs.face.setPointerCapture(event.pointerId)
                } catch (exception) {
                    // Capture is a convenience; without it the drag simply ends
                    // when the pointer leaves the face.
                }

                this.applyPointer(event)
                event.preventDefault()
            },

            onPointerMove(event) {
                if (! this.dragging) {
                    return
                }

                this.applyPointer(event)
                event.preventDefault()
            },

            onPointerUp(event) {
                if (! this.dragging) {
                    return
                }

                this.dragging = false
                this.applyPointer(event)
                this.advanceStage()
            },

            /** Where the pointer is on the face, as an angle and a radius. */
            readPointer(event) {
                const rect = this.$refs.face.getBoundingClientRect()
                const scale = FACE_SIZE / rect.width
                const x = ((event.clientX - rect.left) * scale) - FACE_CENTRE
                const y = ((event.clientY - rect.top) * scale) - FACE_CENTRE

                let angle = Math.atan2(x, -y) * (180 / Math.PI)

                if (angle < 0) {
                    angle += 360
                }

                return { angle, radius: Math.sqrt((x * x) + (y * y)) }
            },

            applyPointer(event) {
                const { angle, radius } = this.readPointer(event)

                if (this.stage === 'hour') {
                    const index = Math.round(angle / 30) % 12
                    const isInner = this.config.hoursCycle === 24 && radius < RING_BOUNDARY
                    const numeral = this.config.hoursCycle === 24
                        ? (isInner ? (index === 0 ? 0 : index + 12) : (index === 0 ? 12 : index))
                        : (index === 0 ? 12 : index)

                    if (! this.isNumeralAllowed('hour', numeral)) {
                        return
                    }

                    this.hour = this.hourFromNumeral(numeral, isInner)

                    if (this.minute === null) {
                        this.minute = 0
                    }

                    if (this.second === null && this.config.hasSeconds) {
                        this.second = 0
                    }
                } else {
                    const step = this.stage === 'minute' ? this.config.minutesStep : this.config.secondsStep
                    const value = this.snap(Math.round(angle / 6) % 60, step, 60)

                    if (! this.isNumeralAllowed(this.stage, value)) {
                        return
                    }

                    this[this.stage] = value
                }

                this.syncText()
            },

            /**
             * A numeral to the hour it means. On the 12-hour face the numeral is
             * half the answer and the AM/PM switch is the other half.
             */
            hourFromNumeral(numeral, isInner) {
                if (this.config.hoursCycle === 24) {
                    return isInner ? numeral : numeral % 24
                }

                const base = numeral % 12

                return this.meridiem === 'pm' ? base + 12 : base
            },

            advanceStage() {
                if (this.stage === 'hour') {
                    this.stage = 'minute'

                    return
                }

                if (this.stage === 'minute' && this.config.hasSeconds) {
                    this.stage = 'second'

                    return
                }

                if (this.config.closeOnComplete) {
                    this.confirm()
                }
            },

            // ------------------------------------------------------------------
            // Typing
            // ------------------------------------------------------------------

            onTyped(part, event) {
                const digits = event.target.value.replace(/\D/g, '').slice(0, 2)

                this[`${part}Text`] = digits

                if (digits === '') {
                    return
                }

                const typed = parseInt(digits, 10)

                if (part === 'hour') {
                    const max = this.config.hoursCycle === 24 ? 23 : 12
                    const min = this.config.hoursCycle === 24 ? 0 : 1

                    if (typed > max || (digits.length === 2 && typed < min)) {
                        return
                    }

                    this.hour = this.config.hoursCycle === 24
                        ? typed
                        : this.hourFromNumeral(typed, false)

                    if (this.minute === null) {
                        this.minute = 0
                    }
                } else {
                    if (typed > 59) {
                        return
                    }

                    this[part] = typed
                }

                // Two digits is as much as any part takes, and a leading digit
                // that cannot be extended is finished as soon as it is typed.
                const cannotExtend = part === 'hour'
                    ? typed > (this.config.hoursCycle === 24 ? 2 : 1)
                    : typed > 5

                if (digits.length === 2 || cannotExtend) {
                    this.$nextTick(() => this.focusNextPart(part))
                }
            },

            focusNextPart(part) {
                if (part === 'hour') {
                    this.stage = 'minute'
                    this.$refs.minuteInput?.focus()

                    return
                }

                if (part === 'minute' && this.config.hasSeconds) {
                    this.stage = 'second'
                    this.$refs.secondInput?.focus()
                }
            },

            commitTyped() {
                this.pullIntoBounds()
                this.syncText()
            },

            nudge(direction) {
                const part = this.stage

                if (part === 'hour') {
                    const step = this.config.hoursStep
                    const current = this.hour ?? 0

                    this.hour = (current + (direction * step) + 24) % 24
                } else {
                    const step = part === 'minute' ? this.config.minutesStep : this.config.secondsStep
                    const current = this[part] ?? 0

                    this[part] = (current + (direction * step) + 60) % 60

                    if (this.hour === null) {
                        this.hour = 0
                    }
                }

                this.pullIntoBounds()
                this.syncText()
            },

            syncText() {
                this.hourText = this.hour === null
                    ? ''
                    : (this.config.hoursCycle === 24
                        ? pad2(this.hour)
                        : String(this.hour % 12 === 0 ? 12 : this.hour % 12))

                this.minuteText = this.minute === null ? '' : pad2(this.minute)
                this.secondText = this.second === null ? '' : pad2(this.second)
            },

            // ------------------------------------------------------------------
            // Bounds
            // ------------------------------------------------------------------

            snap(value, step, wrap) {
                if (step <= 1) {
                    return value
                }

                return (Math.round(value / step) * step) % wrap
            },

            /**
             * Drag the draft back inside the bounds. Typing and the arrow keys can
             * reach a time the dial would have refused, and a field that quietly
             * keeps an impossible value only fails later, at validation.
             */
            pullIntoBounds() {
                if (this.hour === null) {
                    return
                }

                const min = parseBound(this.config.minTime)
                const max = parseBound(this.config.maxTime)
                const current = toSeconds(this.hour, this.minute ?? 0, this.second ?? 0)
                const bounded = clamp(current, min ?? current, max ?? current)

                if (bounded === current) {
                    return
                }

                this.hour = Math.floor(bounded / 3600)
                this.minute = Math.floor((bounded % 3600) / 60)
                this.second = this.config.hasSeconds ? bounded % 60 : 0
            },
        }))
    })
})()
