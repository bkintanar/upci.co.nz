<template>
    <nav v-if="items.length" class="border-l-4 border-brand-green-700 pl-[18px] py-0.5 my-7" aria-label="Contents">
        <span class="block text-sm font-semibold uppercase tracking-wide text-brand-green-700 mb-2">
            {{ label }}
        </span>
        <ol class="list-none m-0 p-0">
            <li
                v-for="(item, index) in items"
                :key="item.href"
                class="text-[15px] py-1.5"
            >
                <!-- Numbered by content, not by CSS counter alone: the number is
                     part of the reference ("see 3 below") and should survive
                     copy-paste and a screen reader. -->
                <span class="text-brand-grey-600 mr-2 tabular-nums">{{ index + 1 }}.</span>
                <a
                    :href="item.href"
                    class="text-brand-ink font-semibold underline underline-offset-2 hover:text-brand-green-700 transition-colors"
                >{{ item.label }}</a>
            </li>
        </ol>
    </nav>
</template>

<script>
import { defineComponent } from 'vue'

/**
 * In-page navigation, ported from the approved B2 screens (§13.1).
 *
 * The third thing B lacked for interior pages: a long page had no way to show
 * its own structure or let a reader jump within it.
 */
export default defineComponent({
    name: 'Contents',
    props: {
        items: { type: Array, default: () => [] },
        label: { type: String, default: 'Contents' },
    },
})
</script>
