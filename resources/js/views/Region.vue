<template>
    <div>
        <div v-if="loading" class="max-w-7xl mx-auto px-4 py-24 text-center text-slate-500">
            Loading region...
        </div>

        <div v-else-if="error" class="max-w-7xl mx-auto px-4 py-24 text-center">
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Region not found</h1>
            <p class="text-slate-500 mb-6">{{ error }}</p>
            <router-link to="/regions" class="text-blue-600 hover:text-blue-700 font-medium">
                Back to all regions
            </router-link>
        </div>

        <template v-else-if="region">
            <!-- Hero -->
            <section class="bg-gradient-to-br from-blue-700 via-blue-800 to-slate-900 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <router-link to="/regions"
                                 class="inline-flex items-center text-blue-200 hover:text-white text-sm mb-6">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        All regions
                    </router-link>

                    <div class="flex items-center gap-6">
                        <img v-if="region.logo_url" :src="region.logo_url" :alt="`${region.name} logo`"
                             class="h-20 w-auto object-contain bg-white/10 rounded-xl p-2">
                        <div>
                            <h1 class="text-4xl md:text-5xl font-bold mb-2">{{ region.name }}</h1>
                            <p v-if="region.presbyter_name" class="text-blue-100">
                                Presbyter: {{ region.presbyter_name }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Message -->
            <section v-if="region.intro" class="py-16 bg-white">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6">A message from the region</h2>
                    <div class="prose prose-slate max-w-none" v-html="renderMarkdown(region.intro)"></div>
                </div>
            </section>

            <!-- Churches -->
            <section class="py-16 bg-slate-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-8">
                        Churches
                        <span class="text-slate-400 font-normal text-lg">({{ region.churches.length }})</span>
                    </h2>

                    <div v-if="region.churches.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="church in region.churches" :key="church.id"
                             class="bg-white rounded-xl border border-slate-200 p-6">
                            <h3 class="font-bold text-slate-900 mb-1">{{ church.name }}</h3>
                            <p v-if="church.city" class="text-sm text-slate-500 mb-3">{{ church.city }}</p>
                            <p v-if="church.pastor_name" class="text-sm text-slate-600 mb-3">
                                {{ church.pastor_name }}
                            </p>
                            <!-- Same honesty as the locator: a church with no
                                 coordinates is still listed, and says why it is
                                 not on the map rather than silently vanishing. -->
                            <p v-if="!church.has_coordinates" class="text-xs text-slate-400">
                                Not on the map yet
                            </p>
                        </div>
                    </div>

                    <p v-else class="text-slate-500">No churches are listed for this region yet.</p>
                </div>
            </section>

            <!-- Events -->
            <section class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-8">Regional events</h2>

                    <div v-if="region.events.length" class="space-y-4">
                        <div v-for="event in region.events" :key="event.id"
                             class="flex items-start gap-6 bg-slate-50 rounded-xl p-6 border border-slate-200">
                            <div class="text-center shrink-0 w-16">
                                <div class="text-2xl font-bold text-blue-600">{{ dayOf(event.start_date) }}</div>
                                <div class="text-xs uppercase text-slate-500">{{ monthOf(event.start_date) }}</div>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-bold text-slate-900 mb-1">{{ event.name }}</h3>
                                <p v-if="event.location" class="text-sm text-slate-500">{{ event.location }}</p>
                                <a v-if="event.url" :href="event.url" target="_blank" rel="noopener"
                                   class="text-sm text-blue-600 hover:text-blue-700 font-medium">More info</a>
                            </div>
                        </div>
                    </div>

                    <!-- Deliberately explicit. Every event currently sits in the
                         national calendar, so this is the expected state until
                         they are assigned to regions. -->
                    <p v-else class="text-slate-500">
                        No regional events are scheduled. See the
                        <router-link to="/events" class="text-blue-600 hover:text-blue-700">national calendar</router-link>
                        for events across New Zealand.
                    </p>
                </div>
            </section>

            <!-- Gallery -->
            <section v-if="region.gallery.length" class="py-16 bg-slate-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-8">Gallery</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <figure v-for="item in region.gallery" :key="item.id"
                                class="rounded-xl overflow-hidden border border-slate-200 bg-white">
                            <img :src="item.image_url" :alt="item.title || ''" loading="lazy"
                                 class="w-full h-48 object-cover">
                            <figcaption v-if="item.title" class="p-3 text-sm text-slate-600">
                                {{ item.title }}
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { marked } from 'marked'

export default defineComponent({
    name: 'Region',
    setup() {
        const route = useRoute()
        const region = ref(null)
        const loading = ref(true)
        const error = ref(null)

        const renderMarkdown = (s) => (s ? marked.parse(s, { breaks: true, gfm: true }) : '')

        // Parsed as parts rather than through Date: "2026-09-01" is parsed as
        // UTC midnight, which renders as the previous day in NZ time.
        const dayOf = (d) => (d ? d.split('-')[2] : '')
        const monthOf = (d) => {
            if (!d) return ''
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            return months[parseInt(d.split('-')[1], 10) - 1] || ''
        }

        const fetchRegion = async (slug) => {
            if (!slug) return
            loading.value = true
            error.value = null
            region.value = null
            try {
                const res = await fetch(`/api/regions/${slug}`)
                const body = await res.json()
                if (body.success && body.data) {
                    region.value = body.data
                } else {
                    error.value = body.message || 'Region not found'
                }
            } catch (e) {
                error.value = e.message || 'Failed to load region'
            } finally {
                loading.value = false
            }
        }

        onMounted(() => fetchRegion(route.params.slug))
        watch(() => route.params.slug, (slug) => {
            if (slug) fetchRegion(slug)
        })

        return { region, loading, error, renderMarkdown, dayOf, monthOf }
    }
})
</script>
