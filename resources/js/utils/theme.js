/**
 * Brand class bundles for Direction B.
 *
 * Every class here is a LITERAL string. Tailwind's JIT scanner reads source
 * files as plain text, so a constructed class like `bg-brand-green-${weight}`
 * is never emitted into the stylesheet and silently renders unstyled. That is
 * also why there is no safelist regex: a safelist would paper over the dynamic
 * construction rather than remove it, and it inflates the bundle with classes
 * nothing uses.
 *
 * Mirrors the shape of eventStatus.js — a lookup returning an object of class
 * strings keyed by render slot, so templates do e.g. `surface('panel').card`.
 */

// Department hero treatments. Previously inlined in Department.vue, which meant
// the only list of valid themes lived inside one component.
//
// T49② (client-approved 2026-08-17): respread onto the brand family. These were
// Tailwind's stock blue-700/emerald-700/pink-600/amber-500/purple-700/indigo-700
// and terminated in `slate-900`, a neutral from nowhere in the mark. Each
// department still owns its own hue — they must stay distinguishable, since
// collapsing them into green would make Prayer and Men's look like the same
// page — but all six now share the brand's lightness and chroma and land on
// brand-ink. See the `dept` block in tailwind.config.js for the hue angles.
//
// The KEYS are the legacy identifiers stored in `departments.color_theme`
// ('green', 'pink', …). They are deliberately left alone: renaming them to
// match the new hues would mean a data migration whose only benefit is tidier
// strings, and any row that failed to migrate would silently fall back to blue.
// The key names a department's slot; the token names its colour.
const DEPARTMENT_THEMES = {
    blue: 'bg-gradient-to-br from-brand-dept-blue-700 via-brand-dept-blue-900 to-brand-ink',
    green: 'bg-gradient-to-br from-brand-dept-green-700 via-brand-dept-green-900 to-brand-ink',
    pink: 'bg-gradient-to-br from-brand-dept-pink-700 via-brand-dept-pink-900 to-brand-ink',
    yellow: 'bg-gradient-to-br from-brand-dept-yellow-700 via-brand-dept-yellow-900 to-brand-ink',
    purple: 'bg-gradient-to-br from-brand-dept-purple-700 via-brand-dept-purple-900 to-brand-ink',
    indigo: 'bg-gradient-to-br from-brand-dept-indigo-700 via-brand-dept-indigo-900 to-brand-ink',
}

/**
 * Hero background for a department's colour_theme, falling back to blue.
 *
 * @param {string|null|undefined} theme
 * @returns {string}
 */
export function departmentHeroClasses(theme) {
    return DEPARTMENT_THEMES[theme] || DEPARTMENT_THEMES.blue
}

/**
 * The brand surfaces. Direction B is plain and institutional — flat fills and
 * a rule, not gradients and shadows.
 *
 * @param {'page'|'panel'|'inverse'|'accent'} kind
 * @returns {{ surface: string, heading: string, body: string, muted: string, rule: string }}
 */
export function surface(kind) {
    switch (kind) {
        case 'inverse':
            return {
                surface: 'bg-brand-ink text-white',
                heading: 'text-white',
                body: 'text-brand-grey-200',
                muted: 'text-brand-grey-400',
                rule: 'border-brand-green-700',
            }
        case 'accent':
            return {
                surface: 'bg-brand-green-100',
                heading: 'text-brand-green-900',
                body: 'text-brand-ink',
                muted: 'text-brand-grey-600',
                rule: 'border-brand-green-700',
            }
        case 'panel':
            return {
                surface: 'bg-white border border-brand-grey-200',
                heading: 'text-brand-ink',
                body: 'text-brand-ink',
                muted: 'text-brand-grey-600',
                rule: 'border-brand-grey-200',
            }
        case 'page':
        default:
            return {
                surface: 'bg-brand-paper',
                heading: 'text-brand-ink',
                body: 'text-brand-ink',
                muted: 'text-brand-grey-600',
                rule: 'border-brand-grey-200',
            }
    }
}

/**
 * Action treatments. Clay is the accent, used sparingly — green is the brand
 * and does the structural work.
 *
 * @param {'primary'|'secondary'|'accent'} kind
 * @returns {string}
 */
export function button(kind) {
    switch (kind) {
        case 'accent':
            return 'inline-flex items-center px-5 py-3 font-semibold text-white bg-brand-clay-600 hover:bg-brand-ink transition-colors'
        case 'secondary':
            return 'inline-flex items-center px-5 py-3 font-semibold text-brand-green-900 bg-brand-green-100 hover:bg-brand-grey-200 transition-colors'
        case 'primary':
        default:
            return 'inline-flex items-center px-5 py-3 font-semibold text-white bg-brand-green-700 hover:bg-brand-green-900 transition-colors'
    }
}

/**
 * Error treatment (D13). Scoped to errors only — the two-hue rule stands
 * everywhere else, and neither green nor clay can carry "something is wrong".
 *
 * @returns {{ summary: string, field: string, message: string }}
 */
export function errorClasses() {
    return {
        summary: 'border-l-4 border-brand-error bg-white p-4 text-brand-ink',
        field: 'border-2 border-brand-error focus:ring-brand-error',
        message: 'text-brand-error text-sm font-medium',
    }
}
