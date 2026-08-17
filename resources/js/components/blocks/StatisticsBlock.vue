<template>
    <section class="py-16 bg-brand-green-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data.heading" class="text-3xl font-bold text-brand-ink mb-2">{{ data.heading }}</h2>
            <p v-if="data.lede" class="text-brand-grey-600 mb-8">{{ data.lede }}</p>

            <BlockState
                :loading="loading" :error="error" :items="items"
                :empty-message="data.empty_message" :on-retry="load"
            >
                <template #loading>Loading figures…</template>

                <dl class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div v-for="stat in items" :key="stat.label">
                        <dd class="text-4xl lg:text-5xl font-bold text-brand-green-900 tabular-nums">{{ stat.value }}</dd>
                        <dt class="mt-1 text-sm text-brand-grey-600">{{ stat.label }}</dt>
                    </div>
                </dl>
            </BlockState>
        </div>
    </section>
</template>

<script>
import { defineComponent } from 'vue'
import BlockState from '../BlockState.vue'
import { useBlockData } from '../../composables/useBlockData'

/**
 * Figures counted from live records, never typed.
 *
 * The homepage previously stated these as prose and three of four had drifted:
 * 10 established churches against 9, 3 daughter works against none, 2 preaching
 * points against 1, 12 potential home groups against none. Counting at request
 * time means they cannot be wrong again, and a category with no records is
 * omitted rather than published as a zero.
 *
 * <dl> rather than divs: these are term/value pairs, and the number is the
 * value while the label is the term.
 */
export default defineComponent({
    name: 'StatisticsBlock',
    components: { BlockState },
    props: { data: { type: Object, required: true } },
    setup() {
        const { items, loading, error, load } = useBlockData('/api/church-statistics')
        return { items, loading, error, load }
    },
})
</script>
