<template>
    <!-- Direction D2's section head: a sentence-case heading sitting on a 3px
         rule, with an optional "more" link at the right end of the rule. -->
    <div :class="['flex items-end justify-between gap-4', rule ? 'pb-3.5 mb-6 border-b-[3px] border-brand-grey-200' : '']">
        <component
            :is="level"
            :id="id"
            class="text-[27px] md:text-[34px] leading-tight font-bold tracking-[-0.015em] text-brand-ink"
        >{{ heading }}</component>
        <MenuLink
            v-if="moreText && moreUrl"
            :item="{ url: moreUrl }"
            class="inline-flex items-center gap-2 pb-1 text-[15px] font-bold text-brand-green-700 whitespace-nowrap hover:underline underline-offset-4"
        >{{ moreText }} <ArrowIcon /></MenuLink>
    </div>
</template>

<script>
import { defineComponent } from 'vue'
import MenuLink from '../MenuLink.vue'
import ArrowIcon from './ArrowIcon.vue'

export default defineComponent({
    name: 'SectionHead',
    components: { MenuLink, ArrowIcon },
    props: {
        heading: { type: String, required: true },
        id: { type: String, default: null },
        level: { type: String, default: 'h2' },
        rule: { type: Boolean, default: true },
        moreText: { type: String, default: null },
        moreUrl: { type: String, default: null },
    },
})
</script>
