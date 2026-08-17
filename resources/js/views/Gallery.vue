<template>
    <div>
        <section class="bg-gradient-to-br from-blue-700 via-blue-800 to-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Gallery</h1>
                <p class="text-xl text-blue-100 max-w-3xl">
                    Moments from across UPCI New Zealand.
                </p>
            </div>
        </section>

        <section class="py-10 bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="active = tab.key"
                        :class="[
                            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                            active === tab.key
                                ? 'bg-blue-600 text-white'
                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Keyed so switching tabs remounts the grid and refetches, rather
             than showing the previous filter's images under a new label. -->
        <GalleryGrid
            :key="active"
            :department="activeTab.department"
            :region="activeTab.region"
            :general="activeTab.general"
            :heading="null"
            section-class="py-12 bg-white"
        />

        <div v-if="!loadingTabs && tabs.length === 1" class="max-w-7xl mx-auto px-4 pb-16 text-slate-500">
            No departments or regions have galleries yet.
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue'
import GalleryGrid from '../components/GalleryGrid.vue'

export default defineComponent({
    name: 'Gallery',
    components: { GalleryGrid },
    setup() {
        const active = ref('all')
        const departments = ref([])
        const regions = ref([])
        const loadingTabs = ref(true)

        // "All" passes no filter at all, which the endpoint reads as
        // everything published regardless of owner.
        const tabs = computed(() => [
            { key: 'all', label: 'All' },
            { key: 'general', label: 'General' },
            ...regions.value.map(r => ({ key: `region:${r.slug}`, label: r.name })),
            ...departments.value.map(d => ({ key: `department:${d.slug}`, label: d.name })),
        ])

        const activeTab = computed(() => {
            const [kind, slug] = active.value.split(':')
            return {
                department: kind === 'department' ? slug : null,
                region: kind === 'region' ? slug : null,
                general: kind === 'general',
            }
        })

        onMounted(async () => {
            // Tab failures are non-fatal: the All and General tabs still work,
            // so a departments outage narrows the page rather than breaking it.
            const load = async (url, target) => {
                try {
                    const res = await fetch(url)
                    const body = await res.json()
                    if (body.success && body.data) target.value = body.data
                } catch (e) {
                    target.value = []
                }
            }

            await Promise.all([
                load('/api/departments', departments),
                load('/api/regions', regions),
            ])
            loadingTabs.value = false
        })

        return { active, tabs, activeTab, loadingTabs }
    }
})
</script>
