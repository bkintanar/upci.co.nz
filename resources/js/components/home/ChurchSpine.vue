<template>
    <!-- The long thin country: congregations on a north-to-south line, grouped
         by region, name and town only. The line, markers and poles are drawn
         with positioned spans and hidden from assistive technology; the region
         headings and list carry the structure. -->
    <div class="relative pl-[30px]">
        <span class="absolute left-[7px] top-[30px] bottom-[30px] w-0.5 bg-brand-grey-400" aria-hidden="true"></span>

        <p v-if="showPoles" class="relative mb-2.5 text-xs font-bold leading-5 tracking-[0.14em] uppercase text-brand-muted">
            <span class="absolute -left-7 top-[3px] w-0 h-0 border-x-[7px] border-x-transparent border-b-[10px] border-b-brand-grey-400" aria-hidden="true"></span>
            North
        </p>

        <div v-for="(group, i) in groups" :key="group.slug" :class="['relative', i > 0 ? 'mt-[18px]' : '']">
            <h3
                v-if="group.name"
                class="relative flex items-baseline justify-between gap-3 py-2 text-[13px] font-extrabold tracking-[0.12em] uppercase text-brand-green-900 border-b border-brand-grey-200"
            >
                <span class="absolute -left-[29px] top-[11px] w-3.5 h-3.5 bg-brand-green-700 border-[3px] border-brand-paper shadow-[0_0_0_1px_theme(colors.brand.green.700)]" aria-hidden="true"></span>
                {{ group.name }}
                <span class="text-[13px] font-semibold tracking-normal normal-case text-brand-muted">
                    {{ group.churches.length }}<span class="sr-only"> {{ group.churches.length === 1 ? 'congregation' : 'congregations' }}</span>
                </span>
            </h3>
            <ul>
                <li
                    v-for="church in group.churches"
                    :key="church.id"
                    class="relative py-2 border-b border-dotted border-brand-grey-400 sm:flex sm:items-baseline sm:gap-4"
                >
                    <span class="absolute -left-[26px] top-[17px] w-2 h-2 rounded-full bg-brand-paper border-2 border-brand-green-700" aria-hidden="true"></span>
                    <span class="block text-base font-semibold leading-snug text-brand-ink">{{ church.name }}</span>
                    <span v-if="church.city" class="block text-[15px] leading-snug text-brand-muted sm:ml-auto sm:text-right sm:whitespace-nowrap">{{ church.city }}</span>
                </li>
            </ul>
        </div>

        <p v-if="showPoles" class="relative mt-3.5 text-xs font-bold leading-5 tracking-[0.14em] uppercase text-brand-muted">
            <span class="absolute -left-7 bottom-[3px] w-0 h-0 border-x-[7px] border-x-transparent border-t-[10px] border-t-brand-grey-400" aria-hidden="true"></span>
            South
        </p>
    </div>
</template>

<script>
import { defineComponent, computed } from 'vue'

export default defineComponent({
    name: 'ChurchSpine',
    props: {
        // From groupChurchesByRegion(): [{ slug, name, churches: [...] }]
        groups: { type: Array, required: true },
    },
    setup(props) {
        // North and South only mean something when the list runs region by
        // region; an ungrouped list has no compass.
        const showPoles = computed(() => props.groups.some((group) => group.name))

        return { showPoles }
    },
})
</script>
