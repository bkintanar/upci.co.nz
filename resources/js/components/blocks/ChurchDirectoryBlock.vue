<template>
    <!-- D2 makes this the page, not a section of it: the congregations are the
         homepage's primary content, so this sits on paper rather than on a grey
         band that would read as secondary. -->
    <section class="py-16 bg-brand-paper">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data.heading" class="text-3xl font-bold text-brand-ink mb-8">{{ data.heading }}</h2>

            <BlockState
                :loading="loading" :error="error" :items="visible"
                :empty-message="data.empty_message" :on-retry="load"
            >
                <template #loading>Loading churches…</template>

                <div v-for="group in groups" :key="group.slug" class="mb-10 last:mb-0">
                    <!-- The region name is the organising device in D2, so it
                         carries the brand rule rather than a grey hairline. -->
                    <div v-if="grouped" class="flex items-baseline justify-between mb-4 pb-2 border-b-2 border-brand-green-700">
                        <h3 class="text-lg font-bold text-brand-ink">{{ group.name }}</h3>
                        <span class="text-sm text-brand-grey-600">
                            {{ group.churches.length }} {{ group.churches.length === 1 ? 'church' : 'churches' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="church in group.churches"
                            :key="church.id"
                            class="bg-white border border-brand-grey-200 p-6"
                        >
                            <h4 class="font-bold text-brand-ink mb-1">{{ church.name }}</h4>
                            <p v-if="church.city" class="text-sm text-brand-grey-600 mb-2">{{ church.city }}</p>
                            <p v-if="church.pastor" class="text-sm text-brand-ink">{{ church.pastor }}</p>
                            <!-- Same honesty as the locator: a church with no
                                 coordinates is listed and says why it is not
                                 mappable, rather than being dropped. -->
                            <p v-if="!church.has_coordinates" class="text-xs text-brand-grey-400 mt-2">
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

            // Unassigned sorts last; everything else keeps API order, which the
            // churches endpoint now sorts by the regions table's own
            // sort_order. It did NOT before — it ordered by featured-then-name,
            // so this comment described an ordering that never existed and the
            // homepage rendered Southern above Northern.
            return [...map.values()].sort((a, b) =>
                (a.slug === 'unassigned' ? 1 : 0) - (b.slug === 'unassigned' ? 1 : 0)
            )
        })

        return { visible, groups, grouped, loading, error, load }
    },
})
</script>
