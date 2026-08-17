<template>
    <!-- Loading -->
    <div v-if="loading" class="py-10 text-center text-slate-500" role="status" aria-live="polite">
        <slot name="loading">Loading…</slot>
    </div>

    <!-- Error: a failed request is not an empty section, and says so. -->
    <div v-else-if="error" class="py-10 text-center">
        <p class="text-slate-700 font-semibold mb-1">
            <slot name="error-title">This section couldn't be loaded.</slot>
        </p>
        <p class="text-slate-500 text-sm mb-4">{{ error }}</p>
        <button
            v-if="onRetry"
            type="button"
            @click="onRetry"
            class="px-4 py-2 bg-brand-green-700 text-white rounded-lg text-sm hover:bg-brand-green-900 transition-colors"
        >
            Try again
        </button>
    </div>

    <!-- Empty: correct, not broken. Uses the author's own wording. -->
    <p v-else-if="isEmpty" class="py-10 text-center text-slate-500">
        {{ emptyMessage || 'Nothing to show here yet.' }}
    </p>

    <slot v-else />
</template>

<script>
import { defineComponent, computed } from 'vue'

/**
 * The three states a data-bound block can be in (§10).
 *
 * Loading, failed and genuinely-empty look identical if a block just renders
 * whatever came back. They are different facts and a visitor should be able to
 * tell them apart — an empty grid meaning "still loading", "the request broke"
 * and "no events scheduled" is how the general gallery sat showing its empty
 * state for months without anyone noticing the filter never matched.
 *
 * The empty message is authored per block, because only the author knows
 * whether "No events scheduled" or "Dates for next year are being confirmed"
 * is the true thing to say.
 */
export default defineComponent({
    name: 'BlockState',
    props: {
        loading: { type: Boolean, default: false },
        error: { type: [String, null], default: null },
        items: { type: Array, default: () => [] },
        emptyMessage: { type: [String, null], default: null },
        onRetry: { type: [Function, null], default: null },
    },
    setup(props) {
        // Only empty once a load has actually finished, or the empty message
        // flashes before the first response arrives.
        const isEmpty = computed(() => !props.loading && !props.error && props.items.length === 0)

        return { isEmpty }
    },
})
</script>
