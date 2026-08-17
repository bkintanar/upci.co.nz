import { ref } from 'vue'

/**
 * Site-wide settings, fetched once per page load.
 *
 * Navbar and Footer both mount on every route, so a naive fetch in each would
 * double every request. The promise is cached at module scope: the first
 * caller triggers the request, the rest await the same one.
 *
 * On failure the refs stay null and callers fall back to their bundled asset,
 * so a settings outage degrades to the shipped defaults rather than a
 * logo-shaped hole.
 */
const settings = ref(null)
let inFlight = null

export function useSiteSettings() {
    if (!inFlight) {
        inFlight = fetch('/api/site-settings')
            .then((r) => (r.ok ? r.json() : null))
            .then((json) => {
                if (json?.success) settings.value = json.data
            })
            .catch(() => {
                // leave settings null — callers use their bundled fallback
            })
    }

    return { settings }
}
