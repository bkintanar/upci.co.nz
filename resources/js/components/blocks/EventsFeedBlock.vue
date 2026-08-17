<template>
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data.heading" class="text-3xl font-bold text-slate-900 mb-8">{{ data.heading }}</h2>

            <BlockState
                :loading="loading" :error="error" :items="visible"
                :empty-message="data.empty_message" :on-retry="load"
            >
                <template #loading>Loading events…</template>

                <div class="space-y-4">
                    <article
                        v-for="event in visible"
                        :key="event.id"
                        class="flex items-start gap-6 bg-slate-50 rounded-xl p-6 border border-slate-200"
                    >
                        <div class="text-center shrink-0 w-16">
                            <div class="text-2xl font-bold text-brand-green-700">{{ dayOf(event.start_date) }}</div>
                            <div class="text-xs uppercase text-slate-500">{{ monthOf(event.start_date) }}</div>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-900 mb-1">{{ event.name }}</h3>
                            <p v-if="event.location" class="text-sm text-slate-500">{{ event.location }}</p>
                            <p v-if="event.region" class="text-xs text-slate-400 mt-1">{{ event.region.name }}</p>
                            <a
                                v-if="event.url" :href="event.url" target="_blank" rel="noopener"
                                class="text-sm text-brand-green-700 hover:underline font-medium"
                            >More info</a>
                        </div>
                    </article>
                </div>
            </BlockState>
        </div>
    </section>
</template>

<script>
import { defineComponent, computed } from 'vue'
import BlockState from '../BlockState.vue'
import { useBlockData } from '../../composables/useBlockData'

export default defineComponent({
    name: 'EventsFeedBlock',
    components: { BlockState },
    props: { data: { type: Object, required: true } },
    setup(props) {
        const url = computed(() => {
            const params = new URLSearchParams()
            if (props.data.scope) params.append('scope', props.data.scope)
            if (props.data.region) params.append('region', props.data.region)
            if (props.data.department) params.append('department', props.data.department)
            // Filtered server-side so a page asking for upcoming events does not
            // download the whole 2026 calendar to discard most of it.
            if (props.data.upcoming_only !== false) params.append('from', new Date().toISOString().slice(0, 10))

            return `/api/events?${params}`
        })

        const { items, loading, error, load } = useBlockData(url)

        // Limit is applied here rather than in the query: the endpoint has no
        // limit parameter, and inventing one for a presentation concern would
        // put layout config into the API.
        const visible = computed(() => {
            const limit = parseInt(props.data.limit, 10)
            return Number.isFinite(limit) && limit > 0 ? items.value.slice(0, limit) : items.value
        })

        // Split as string parts, not through Date: "2026-09-01" parses as UTC
        // midnight and renders as 31 August in New Zealand.
        const dayOf = (d) => (d ? d.split('-')[2] : '')
        const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        const monthOf = (d) => (d ? MONTHS[parseInt(d.split('-')[1], 10) - 1] || '' : '')

        return { visible, loading, error, load, dayOf, monthOf }
    },
})
</script>
