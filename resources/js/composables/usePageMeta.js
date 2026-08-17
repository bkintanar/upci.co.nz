import { onBeforeUnmount, ref } from 'vue'

/**
 * Sets the document title and meta description per route (G14).
 *
 * `meta_description` has been authored on ten pages, returned by the API and
 * never rendered — hand-written SEO text arriving in the browser as unused
 * JSON. `document.title` was never set either, so every route in the SPA
 * showed the same app name in the tab, in bookmarks and in shared links.
 *
 * This is a client-side title, so it does not help crawlers that do not run
 * JS. It does fix the human-facing cases: tab labels, history entries and
 * bookmarks. Real SEO needs server-rendered tags, which is a bigger change
 * than this task.
 */

const SITE_NAME = 'UPCI New Zealand'
const DEFAULT_DESCRIPTION = 'United Pentecostal Church International New Zealand — churches, '
    + 'departments and regions across Aotearoa.'

const MAX_DESCRIPTION = 155

/**
 * The current page's human title, and whether the breadcrumb should be hidden.
 *
 * Module-level rather than per-instance, because the breadcrumb now lives in
 * App.vue — outside every view — and needs the one label a route path cannot
 * supply. "apostolic-bible-college" is not "Apostolic Bible College", and
 * humanising the slug would be a second, worse source of truth for something
 * this composable already computes.
 *
 * `breadcrumbSuppressed` exists for exactly one case: a page that does not
 * exist must not assert a trail to itself. Without it a 404 renders
 * "Home › This Does Not Exist", naming a page that was never there. That used
 * to be handled by accident, because the breadcrumb sat behind CmsPage's
 * `v-if="page"`; moving it to the layout means saying so deliberately.
 */
export const currentPageTitle = ref(null)
export const breadcrumbSuppressed = ref(false)

/**
 * Flattens authored copy into something a meta tag can carry.
 *
 * Several callers pass a markdown body — a department's description begins
 * "**Who we are**" followed by blank lines — and a meta description must be a
 * single line of plain text. Without this the tag ships literal asterisks and
 * newlines, which is worse than no description at all.
 *
 * Deliberately a small strip, not a markdown parser: this only has to remove
 * the marks that appear in authored prose, and pulling a parser in to build a
 * string that gets truncated to 155 characters is not a good trade.
 */
function toPlainText(value) {
    return String(value || '')
        .replace(/!\[[^\]]*\]\([^)]*\)/g, '')      // images
        .replace(/\[([^\]]*)\]\([^)]*\)/g, '$1')   // links keep their text
        .replace(/^#{1,6}\s+/gm, '')               // headings
        .replace(/^\s*>\s?/gm, '')                 // blockquotes
        .replace(/^\s*[-*+]\s+/gm, '')             // list bullets
        .replace(/(\*\*|__|\*|_|`)/g, '')          // emphasis and code
        .replace(/\s+/g, ' ')                      // collapse newlines
        .trim()
}

/**
 * Truncates on a word boundary so the description does not end mid-word.
 */
function truncate(value, limit = MAX_DESCRIPTION) {
    if (value.length <= limit) return value

    const cut = value.slice(0, limit)
    const lastSpace = cut.lastIndexOf(' ')

    return (lastSpace > limit * 0.6 ? cut.slice(0, lastSpace) : cut).trimEnd() + '…'
}

/**
 * Writes a <meta name="..."> value, creating the tag if it is absent.
 */
function setMeta(name, content) {
    if (typeof document === 'undefined') return

    let tag = document.querySelector(`meta[name="${name}"]`)

    if (!tag) {
        tag = document.createElement('meta')
        tag.setAttribute('name', name)
        document.head.appendChild(tag)
    }

    tag.setAttribute('content', content)
}

export function usePageMeta() {
    /**
     * @param {string|null} title  Page title; the site name is appended.
     * @param {string|null} description  Falls back to the site description.
     */
    const setPageMeta = (title, description = null) => {
        if (typeof document === 'undefined') return

        // A page whose title already ends in the site name is left alone —
        // several CMS titles are authored as "... - UPCI New Zealand" and
        // would otherwise read "X - UPCI New Zealand | UPCI New Zealand".
        const trimmed = (title || '').trim()
        document.title = !trimmed
            ? SITE_NAME
            : (trimmed.toLowerCase().includes(SITE_NAME.toLowerCase())
                ? trimmed
                : `${trimmed} | ${SITE_NAME}`)

        // The leaf crumb wants the bare title without the site-name suffix that
        // several CMS titles carry — the same strip the breadcrumb used to do
        // locally on `page.title`.
        currentPageTitle.value = trimmed ? trimmed.split(/\s+[-|]\s+/)[0].trim() : null

        const desc = truncate(toPlainText(description)) || DEFAULT_DESCRIPTION
        setMeta('description', desc)
        setMeta('og:title', document.title)
        setMeta('og:description', desc)
    }

    // Leaving a view resets to the site default. Without this a page with no
    // description of its own inherits whatever the previous route set, which
    // is worse than the generic text.
    onBeforeUnmount(() => {
        if (typeof document === 'undefined') return
        document.title = SITE_NAME
        setMeta('description', DEFAULT_DESCRIPTION)

        // Reset here rather than in a new hook: Vue unmounts the outgoing view
        // before the incoming one mounts, so a title set by the next route is
        // not clobbered. A separate hook would race with that ordering.
        currentPageTitle.value = null
        breadcrumbSuppressed.value = false
    })

    return { setPageMeta }
}
