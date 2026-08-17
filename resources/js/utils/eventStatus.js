const MS_PER_DAY = 24 * 60 * 60 * 1000

/**
 * Classify an event by its dates relative to `now`.
 *
 * @param {{ start_date: string, end_date?: string|null }} event
 * @param {Date} [now=new Date()]
 * @returns {'past' | 'live' | 'soon' | 'future'}
 */
export function getEventStatus(event, now = new Date()) {
    if (!event || !event.start_date) return 'future'

    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const start = new Date(event.start_date + 'T00:00:00')
    const end = new Date((event.end_date || event.start_date) + 'T00:00:00')

    if (end < today) return 'past'
    if (start <= today && today <= end) return 'live'

    const daysUntilStart = Math.ceil((start - today) / MS_PER_DAY)
    if (daysUntilStart <= 7) return 'soon'
    return 'future'
}

/**
 * Tailwind class bundle per status. Shape is an object of class strings
 * keyed by render slot so templates can do e.g. `statusClasses(ev).card`.
 *
 * Keep strings literal so Tailwind's JIT scanner picks them up.
 */
export function eventStatusClasses(status) {
    switch (status) {
        case 'past':
            return {
                card: 'bg-brand-grey-200/40 border-brand-grey-200 opacity-75',
                date: 'text-brand-grey-400',
                title: 'text-brand-grey-600',
                chip: 'bg-brand-grey-200/50 text-brand-grey-600',
                pill: '',
                pillLabel: '',
                dateBlock: 'bg-brand-grey-200 text-brand-grey-600',
            }
        case 'live':
            return {
                card: 'bg-white border-green-200 ring-2 ring-green-300',
                date: 'text-green-700',
                title: 'text-brand-ink',
                chip: 'bg-green-100 text-green-800',
                pill: 'inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-green-600 text-white',
                pillLabel: 'Happening now',
                dateBlock: 'bg-green-600 text-white',
            }
        case 'soon':
            return {
                card: 'bg-white border-amber-200 ring-2 ring-amber-300',
                date: 'text-amber-700',
                title: 'text-brand-ink',
                chip: 'bg-amber-100 text-amber-800',
                pill: 'inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-500 text-white',
                pillLabel: 'This week',
                dateBlock: 'bg-amber-500 text-white',
            }
        case 'future':
        default:
            return {
                card: 'bg-white border-brand-grey-200',
                date: 'text-blue-700',
                title: 'text-brand-ink',
                chip: 'bg-blue-100 text-blue-800',
                pill: '',
                pillLabel: '',
                dateBlock: 'bg-blue-600 text-white',
            }
    }
}

/**
 * Tailwind classes for a department chip, keyed by the department's
 * `color_theme` column (blue|green|pink|yellow|purple|indigo).
 *
 * Literal strings so Tailwind JIT includes them in the bundle.
 */
export function departmentChipClasses(colorTheme) {
    switch (colorTheme) {
        case 'green':
            return 'bg-green-100 text-green-800 border-green-200'
        case 'pink':
            return 'bg-pink-100 text-pink-800 border-pink-200'
        case 'yellow':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200'
        case 'purple':
            return 'bg-purple-100 text-purple-800 border-purple-200'
        case 'indigo':
            return 'bg-indigo-100 text-indigo-800 border-indigo-200'
        case 'blue':
        default:
            return 'bg-blue-100 text-blue-800 border-blue-200'
    }
}
