// Paths Laravel serves itself rather than the SPA. A <router-link> to one of
// these stays inside Vue and lands on the not-found route, so they need a
// plain anchor that does a full page load (e.g. "Minister's Log-in" -> /admin).
const SERVER_PATHS = ['/admin', '/storage']

/**
 * True when a menu URL must render as a plain <a> rather than a <router-link>:
 * absolute URLs, the '#' placeholder, and server-side paths such as /admin.
 */
export function isPlainAnchor(url) {
    if (typeof url !== 'string') return false
    if (url.startsWith('http') || url === '#') return true
    return SERVER_PATHS.some(p => url === p || url.startsWith(`${p}/`))
}
