import DOMPurify from 'dompurify'
import { marked } from 'marked'

/**
 * Markdown → sanitised HTML, for every place the site uses `v-html`.
 *
 * Four views each had their own `renderMarkdown` and none of them sanitised
 * anything. `marked` passes raw HTML straight through by design, and `v-html`
 * inserts it verbatim — so whatever an author typed became live markup on the
 * page. Two live announcements already carry raw `<iframe>`; nothing was
 * stopping a `<script>` from working the same way.
 *
 * That is stored XSS waiting to happen, and the blast radius is wider than the
 * admin: department descriptions, announcements, CMS text blocks, region
 * intros and leadership biographies all render through this path.
 *
 * The embeds are legitimate, so a blanket strip is the wrong fix — it would
 * delete real content. Instead iframes survive only from hosts the
 * organisation actually embeds from, and everything else dangerous goes.
 */

// Hosts whose embeds are expected. Anything else loses its iframe, because an
// iframe from an arbitrary origin can host whatever that origin likes.
const ALLOWED_FRAME_HOSTS = [
    'facebook.com',
    'www.facebook.com',
    'youtube.com',
    'www.youtube.com',
    'youtube-nocookie.com',
    'www.youtube-nocookie.com',
    'player.vimeo.com',
    'google.com',
    'www.google.com',
]

let configured = false

function configure() {
    if (configured) return
    configured = true

    marked.setOptions({ breaks: true, gfm: true })

    DOMPurify.addHook('uponSanitizeElement', (node, data) => {
        if (data.tagName !== 'iframe') return

        const src = node.getAttribute?.('src') || ''
        let host = ''

        try {
            // Relative or malformed src has no business in an iframe here.
            host = new URL(src, window.location.origin).hostname
        } catch {
            node.remove()
            return
        }

        if (!ALLOWED_FRAME_HOSTS.includes(host)) {
            node.remove()
        }
    })

    // target="_blank" without rel gives the opened page a handle back via
    // window.opener. Applied here rather than asked of every author.
    DOMPurify.addHook('afterSanitizeAttributes', (node) => {
        if (node.tagName === 'A' && node.getAttribute('target') === '_blank') {
            node.setAttribute('rel', 'noopener noreferrer')
        }
    })
}

/**
 * @param {string|null|undefined} source  Markdown, possibly containing HTML.
 * @returns {string} HTML safe to pass to v-html.
 */
export function renderMarkdown(source) {
    if (!source) return ''

    configure()

    return DOMPurify.sanitize(marked.parse(String(source)), {
        ADD_TAGS: ['iframe'],
        // Only the attributes an embed genuinely needs. `srcdoc` is excluded
        // deliberately: it carries a whole document inline and would reopen
        // the hole the host allowlist closes.
        ADD_ATTR: ['allow', 'allowfullscreen', 'frameborder', 'scrolling', 'target'],
        FORBID_TAGS: ['style', 'form', 'input', 'button'],
        FORBID_ATTR: ['srcdoc', 'formaction', 'style'],
    })
}
