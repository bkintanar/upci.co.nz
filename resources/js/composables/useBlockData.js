import { ref, isRef, watch, onMounted } from 'vue'

/**
 * Accepts a plain string, a getter, or a ref/computed.
 *
 * Getting this wrong is silent and confusing: a computed ref is an object, so
 * `typeof source === 'function'` is false and the ref itself reaches fetch(),
 * which stringifies it to "[object Object]" and 404s. The block then shows its
 * error state and looks like a broken endpoint rather than a broken call.
 */
function resolveUrl(source) {
    if (typeof source === 'function') return source()
    if (isRef(source)) return source.value

    return source
}

/**
 * Fetch state for a data-bound CMS block (§9, §10).
 *
 * The static blocks render whatever the author typed, so they cannot fail. A
 * data-bound block can: the request can be in flight, error, or succeed with
 * nothing in it. Those are three different things and a visitor should be able
 * to tell them apart — an empty grid that means "loading", "broken" and
 * "nothing scheduled" is the same defect as the gallery that silently showed
 * its empty state for months because the filter never matched.
 *
 * `empty` is deliberately distinct from `error`. A region with no events is
 * correct and gets the author's own wording; a failed request is not, and gets
 * a retry.
 */
export function useBlockData(urlFactory, options = {}) {
    const { immediate = true } = options

    const items = ref([])
    const loading = ref(false)
    const error = ref(null)
    const loaded = ref(false)

    const load = async () => {
        const url = resolveUrl(urlFactory)

        if (!url) {
            items.value = []
            loaded.value = true
            return
        }

        loading.value = true
        error.value = null

        try {
            const response = await fetch(url)
            const body = await response.json()

            if (!response.ok || !body.success) {
                // A 422 from a bad filter reaches here rather than being
                // swallowed — an author who mistypes a region slug should see
                // that the block is misconfigured, not an empty section.
                throw new Error(body.message || `Request failed (${response.status})`)
            }

            items.value = Array.isArray(body.data) ? body.data : []
        } catch (e) {
            error.value = e.message || 'Could not load this section'
            items.value = []
        } finally {
            loading.value = false
            loaded.value = true
        }
    }

    if (immediate) {
        onMounted(load)
    }

    // Re-fetch when the URL changes — an author switching a block's region or
    // scope would otherwise keep seeing the previous filter's results. Watches
    // getters and refs alike; a plain string cannot change, so nothing to do.
    if (typeof urlFactory === 'function' || isRef(urlFactory)) {
        watch(() => resolveUrl(urlFactory), load)
    }

    return { items, loading, error, loaded, load }
}
