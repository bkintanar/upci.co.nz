<template>
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data.heading" class="text-3xl font-bold text-slate-900 mb-8">{{ data.heading }}</h2>

            <BlockState
                :loading="loading" :error="error" :items="items"
                :empty-message="data.empty_message" :on-retry="load"
            >
                <template #loading>Loading regions…</template>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <router-link
                        v-for="region in items"
                        :key="region.slug"
                        :to="`/regions/${region.slug}`"
                        class="group bg-white rounded-2xl border border-slate-200 p-8 hover:border-brand-green-700 hover:shadow-xl transition-all flex flex-col no-underline"
                    >
                        <template v-if="data.show_logos !== false">
                            <img
                                v-if="region.logo_url"
                                :src="region.logo_url"
                                :alt="`${region.name} logo`"
                                class="h-16 w-auto mb-6 object-contain self-start"
                            />
                            <!-- A lettermark rather than a broken image frame:
                                 region logos have not been supplied yet. -->
                            <div
                                v-else
                                class="h-16 w-16 mb-6 rounded-xl bg-brand-green-100 text-brand-green-900 flex items-center justify-center text-2xl font-bold"
                            >{{ region.name.charAt(0) }}</div>
                        </template>

                        <h3 class="text-2xl font-bold text-slate-900 mb-2 group-hover:text-brand-green-700 transition-colors">
                            {{ region.name }}
                        </h3>

                        <p class="text-sm text-slate-500 mb-6">
                            {{ region.churches_count }} {{ region.churches_count === 1 ? 'church' : 'churches' }}
                            <template v-if="region.presbyter_name"> · {{ region.presbyter_name }}</template>
                        </p>

                        <span class="mt-auto text-brand-green-700 font-semibold text-sm">More info →</span>
                    </router-link>
                </div>
            </BlockState>
        </div>
    </section>
</template>

<script>
import { defineComponent } from 'vue'
import BlockState from '../BlockState.vue'
import { useBlockData } from '../../composables/useBlockData'

export default defineComponent({
    name: 'RegionListBlock',
    components: { BlockState },
    props: { data: { type: Object, required: true } },
    setup() {
        const { items, loading, error, load } = useBlockData('/api/regions')
        return { items, loading, error, load }
    },
})
</script>
