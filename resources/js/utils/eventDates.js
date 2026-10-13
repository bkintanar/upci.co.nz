/**
 * Date labels for the homepage's event cards and ministry rows.
 *
 * Dates arrive as "YYYY-MM-DD" and are split as strings, never parsed through
 * Date in local time: "2026-09-01" parses as UTC midnight and renders as
 * 31 August in New Zealand. Weekdays come from Date.UTC on the same parts, so
 * they cannot drift either.
 */

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
const MS_PER_DAY = 24 * 60 * 60 * 1000

function parts(date) {
    const [y, m, d] = String(date).split('-').map((n) => parseInt(n, 10))
    return { y, m, d }
}

function utc(date) {
    const { y, m, d } = parts(date)
    return Date.UTC(y, m - 1, d)
}

function weekday(date, short = false) {
    const name = DAYS[new Date(utc(date)).getUTCDay()]
    return short ? name.slice(0, 3) : name
}

/** Today in the visitor's local calendar, as YYYY-MM-DD. */
export function todayIso(now = new Date()) {
    const pad = (n) => String(n).padStart(2, '0')
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`
}

/** True for a multi-day event whose last day is still to come. */
function spansDays(event) {
    return Boolean(event.end_date) && event.end_date !== event.start_date
}

/**
 * The big figure and its caption on a date tile: { day: '1–22', month: 'Oct' }.
 * Across a month boundary the caption carries the second month:
 * { day: '30', month: 'Oct – 2 Nov' }.
 */
export function tileDate(event) {
    const s = parts(event.start_date)

    if (!spansDays(event)) {
        return { day: String(s.d), month: MONTHS[s.m - 1] }
    }

    const e = parts(event.end_date)

    if (s.y === e.y && s.m === e.m) {
        return { day: `${s.d}–${e.d}`, month: MONTHS[s.m - 1] }
    }

    return { day: String(s.d), month: `${MONTHS[s.m - 1]} – ${e.d} ${MONTHS[e.m - 1]}` }
}

/** "24–25 Oct", for the chip on an event's flyer. */
export function shortRange(event) {
    const { day, month } = tileDate(event)
    return `${day} ${month}`
}

/**
 * When, in words, under an event's title. The date is already on the tile, so
 * this says the day of the week: "Thursday", "Sat – Sun", or for a long run
 * "Thu 1 – Thu 22 Oct".
 */
export function weekdayLine(event) {
    if (!spansDays(event)) {
        return weekday(event.start_date)
    }

    const days = Math.round((utc(event.end_date) - utc(event.start_date)) / MS_PER_DAY) + 1

    if (days <= 3) {
        return `${weekday(event.start_date, true)} – ${weekday(event.end_date, true)}`
    }

    const s = parts(event.start_date)
    const e = parts(event.end_date)

    const startMonth = s.m === e.m ? '' : ` ${MONTHS[s.m - 1]}`

    return `${weekday(event.start_date, true)} ${s.d}${startMonth} – ${weekday(event.end_date, true)} ${e.d} ${MONTHS[e.m - 1]}`
}

/** "Sat 14 Nov". */
export function shortDate(date) {
    const { d, m } = parts(date)
    return `${weekday(date, true)} ${d} ${MONTHS[m - 1]}`
}

/**
 * For a multi-day event already under way: { day: 14, of: 22 }. Null otherwise,
 * including on a single-day event's own day, which needs no counting.
 */
export function runningDay(event, today = todayIso()) {
    if (!spansDays(event) || today < event.start_date || today > event.end_date) {
        return null
    }

    return {
        day: Math.round((utc(today) - utc(event.start_date)) / MS_PER_DAY) + 1,
        of: Math.round((utc(event.end_date) - utc(event.start_date)) / MS_PER_DAY) + 1,
    }
}
