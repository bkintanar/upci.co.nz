<template>
    <header class="border-b border-brand-grey-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-9 pb-7 lg:pt-[52px] lg:pb-10">
            <p v-if="eyebrow" class="text-sm font-semibold uppercase tracking-wide text-brand-green-700 mb-1.5">
                {{ eyebrow }}
            </p>

            <!-- max-w in ch, not px: a title band should break by measure, not
                 by viewport, so a long name wraps sensibly at every width. -->
            <h1 class="text-3xl sm:text-4xl lg:text-[44px] font-bold leading-[1.1] tracking-tight text-brand-ink max-w-[18ch]">
                {{ title }}
            </h1>

            <p v-if="lede" class="mt-4 text-lg text-brand-grey-600 max-w-[52ch]">{{ lede }}</p>
        </div>
    </header>
</template>

<script>
import { defineComponent } from 'vue'

/**
 * The page-title band, ported from the approved B2 screens (§13.1).
 *
 * Direction B's hero states a TASK ("find a church"), which is right for the
 * homepage and wrong everywhere else — an interior page needs to say what it
 * is. Without this, B had nothing to put at the top of any other page.
 */
export default defineComponent({
    name: 'PageHeader',
    props: {
        title: { type: String, required: true },
        lede: { type: String, default: null },
        eyebrow: { type: String, default: null },
    },
})
</script>
