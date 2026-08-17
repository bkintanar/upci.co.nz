<template>
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data.heading" class="text-3xl font-bold text-slate-900 mb-8">{{ data.heading }}</h2>

            <BlockState
                :loading="loading" :error="error" :items="visible"
                :empty-message="data.empty_message" :on-retry="load"
            >
                <template #loading>Loading churches…</template>

                <div v-for="group in groups" :key="group.slug" class="mb-10 last:mb-0">
                    <div v-if="grouped" class="flex items-baseline justify-between mb-4 pb-2 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ group.name }}</h3>
                        <span class="text-sm text-slate-500">
                            {{ group.churches.length }} {{ group.churches.length === 1 ? 'church' : 'churches' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="church in group.churches"
                            :key="church.id"
                            class="bg-white rounded-xl border border-slate-200 p-6"
                        >
                            <h4 class="font-bold text-slate-900 mb-1">{{ church.name }}</h4>
                            <p v-if="church.city" class="text-sm text-slate-500 mb-2">{{ church.city }}</p>
                            <p v-if="church.pastor" class="text-sm text-slate-600">{{ church.pastor }}</p>
                            <!-- Same honesty as the locator: a church with no
                                 coordinates is listed and says why it is not
                                 mappable, rather than being dropped. -->
                            <p v-if="!church.has_coordinates" class="text-xs text-slate-400 mt-2">
                                Not on the map yet
                            </p>
                        </div>
                    </div>
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
    name: 'ChurchDirectoryBlock',
    components: { BlockState },
    props: { data: { type: Object, required: true } },
    setup(props) {
        const url = computed(() => {
            const params = new URLSearchParams()
            // organizational_region, not region: the latter filters the
            // free-text geographic column and never matches a slug.
            if (props.data.region) params.append('organizational_region', props.data.region)

            return `/api/churches?${params}`
        })

        const { items, loading, error, load } = useBlockData(url)

        const visible = computed(() => {
            const limit = parseInt(props.data.limit, 10)
            return Number.isFinite(limit) && limit > 0 ? items.value.slice(0, limit) : items.value
        })

        const grouped = computed(() => props.data.group_by_region !== false)

        const groups = computed(() => {
            if (!grouped.value) {
                return [{ slug: 'all', name: '', churches: visible.value }]
            }

            const map = new Map()

            visible.value.forEach((church) => {
                const slug = church.organizational_region || 'unassigned'
                if (!map.has(slug)) {
                    map.set(slug, {
                        slug,
                        name: church.organizational_region_name || 'Not yet assigned to a region',
                        churches: [],
                    })
                }
                map.get(slug).churches.push(church)
            })

            // Unassigned sorts last; everything else keeps API order, which is
            // the regions' own sort_order.
            return [...map.values()].sort((a, b) =>
                (a.slug === 'unassigned' ? 1 : 0) - (b.slug === 'unassigned' ? 1 : 0)
            )
        })

        return { visible, groups, grouped, loading, error, load }
    },
})
</script>
