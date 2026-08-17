<template>
    <div>
        <!-- Hero -->
        <section class="bg-gradient-to-br from-brand-green-700 via-brand-green-900 to-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Regions</h1>
                <p class="text-xl text-brand-green-100 max-w-3xl">
                    UPCI New Zealand is organised into three regions. Find the one nearest you.
                </p>
            </div>
        </section>

        <section class="py-16 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div v-if="loading" class="text-center py-16 text-slate-500">
                    Loading regions...
                </div>

                <div v-else-if="error" class="text-center py-16">
                    <p class="text-slate-700 font-semibold mb-2">We couldn't load the regions.</p>
                    <p class="text-slate-500 text-sm mb-4">{{ error }}</p>
                    <button @click="fetchRegions"
                            class="px-4 py-2 bg-brand-green-700 text-white rounded-lg text-sm hover:bg-brand-green-900">
                        Try again
                    </button>
                </div>

                <div v-else-if="regions.length" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <router-link
                        v-for="region in regions"
                        :key="region.slug"
                        :to="`/regions/${region.slug}`"
                        class="group bg-white rounded-2xl border border-slate-200 p-8 hover:border-brand-green-700 hover:shadow-xl transition-all duration-300 flex flex-col"
                    >
                        <!-- Falls back to a lettermark rather than a broken image
                             frame: region logos are not supplied yet. -->
                        <img v-if="region.logo_url" :src="region.logo_url" :alt="`${region.name} logo`"
                             class="h-16 w-auto mb-6 object-contain self-start">
                        <div v-else
                             class="h-16 w-16 mb-6 rounded-xl bg-brand-green-100 text-brand-green-900 flex items-center justify-center text-2xl font-bold">
                            {{ region.name.charAt(0) }}
                        </div>

                        <h2 class="text-2xl font-bold text-slate-900 mb-2 group-hover:text-brand-green-700 transition-colors">
                            {{ region.name }}
                        </h2>

                        <p class="text-sm text-slate-500 mb-6">
                            {{ region.churches_count }} {{ region.churches_count === 1 ? 'church' : 'churches' }}
                            <template v-if="region.presbyter_name"> · {{ region.presbyter_name }}</template>
                        </p>

                        <span class="mt-auto text-brand-green-700 font-medium text-sm inline-flex items-center">
                            More info
                            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </router-link>
                </div>

                <p v-else class="text-center py-16 text-slate-500">No regions are published yet.</p>
            </div>
        </section>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue'
import { usePageMeta } from '../composables/usePageMeta'

export default defineComponent({
    name: 'Regions',
    setup() {
        const { setPageMeta } = usePageMeta()
        const regions = ref([])
        const loading = ref(true)
        const error = ref(null)

        const fetchRegions = async () => {
            loading.value = true
            error.value = null
            try {
                const res = await fetch('/api/regions')
                const body = await res.json()
                if (body.success && body.data) {
                    regions.value = body.data
                    setPageMeta('Regions', 'The three regions of UPCI New Zealand and the churches in each.')
                } else {
                    error.value = body.message || 'Regions could not be loaded'
                }
            } catch (e) {
                // Surfaced rather than swallowed: an empty grid and a failed
                // request look identical to a visitor otherwise.
                error.value = e.message || 'Failed to load regions'
            } finally {
                loading.value = false
            }
        }

        onMounted(fetchRegions)

        return { regions, loading, error, fetchRegions }
    }
})
</script>
