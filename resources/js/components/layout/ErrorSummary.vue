<template>
    <!-- tabindex + ref so the parent can move focus here on a failed submit:
         a validation failure that only appears further down the page is
         invisible to anyone not looking at the field they just left. -->
    <div
        v-if="entries.length"
        ref="root"
        tabindex="-1"
        role="alert"
        aria-labelledby="error-summary-title"
        class="border-l-4 border-brand-error bg-white p-4 mb-6 focus:outline-none focus:ring-2 focus:ring-brand-error"
    >
        <h2 id="error-summary-title" class="font-bold text-brand-ink mb-2">
            {{ title }}
        </h2>

        <ul class="list-none m-0 p-0 space-y-1">
            <li v-for="entry in entries" :key="entry.field">
                <!-- Anchored to the field, so the fix is one click away rather
                     than a hunt. -->
                <a
                    :href="`#${entry.field}`"
                    class="text-brand-error font-medium underline underline-offset-2"
                    @click.prevent="focusField(entry.field)"
                >{{ entry.message }}</a>
            </li>
        </ul>
    </div>
</template>

<script>
import { defineComponent, computed, ref, watch, nextTick } from 'vue'

/**
 * Validation summary (D13, §13.3).
 *
 * brand-spec.md commits to two hues and has no red. That is fine for a palette
 * and wrong for forms: failure signalled by weight alone is not available to
 * anyone who cannot see the weight. The error hue exists solely for this, and
 * is used nowhere else.
 *
 * The pattern — a summary at the top, focused on submit, each item linking to
 * its field — is what makes a failed form recoverable by keyboard and screen
 * reader rather than only by eye.
 */
export default defineComponent({
    name: 'ErrorSummary',
    props: {
        // Laravel's shape: { field: ['message', ...] }
        errors: { type: Object, default: () => ({}) },
        title: { type: String, default: 'There is a problem' },
    },
    setup(props) {
        const root = ref(null)

        const entries = computed(() =>
            Object.entries(props.errors || {})
                .filter(([, messages]) => Array.isArray(messages) && messages.length)
                .map(([field, messages]) => ({ field, message: messages[0] }))
        )

        const focusField = (field) => {
            const el = document.getElementById(field)
            el?.focus()
            el?.scrollIntoView({ block: 'center', behavior: 'smooth' })
        }

        // Focus the summary whenever a new set of errors arrives, so the
        // failure announces itself instead of waiting to be found.
        watch(entries, async (value, previous) => {
            if (value.length && value.length !== (previous?.length ?? 0)) {
                await nextTick()
                root.value?.focus()
            }
        })

        return { root, entries, focusField }
    },
})
</script>
