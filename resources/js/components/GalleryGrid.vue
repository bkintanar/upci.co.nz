<template>
    <section v-if="loading || error || items.length" :class="sectionClass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="heading" class="text-2xl font-bold text-slate-900 mb-8">{{ heading }}</h2>

            <div v-if="loading" class="text-slate-500 py-8">Loading gallery...</div>

            <div v-else-if="error" class="py-8">
                <p class="text-slate-600 text-sm mb-3">{{ error }}</p>
                <button @click="load" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    Try again
                </button>
            </div>

            <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <figure
                    v-for="item in items"
                    :key="item.id"
                    class="rounded-xl overflow-hidden border border-slate-200 bg-white"
                >
                    <img
                        :src="item.image_url"
                        :alt="item.title || ''"
                        loading="lazy"
                        class="w-full h-48 object-cover"
                    >
                    <figcaption v-if="item.title" class="p-3 text-sm text-slate-600">
                        {{ item.title }}
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>
</template>

<script>
import { defineComponent, ref, onMounted, watch } from 'vue'

/**
 * One gallery grid for departments, regions and the general gallery
 * (requirement 2). Pass exactly one of `department`, `region` or `general`;
 * the component reads the shared /api/gallery endpoint rather than each page
 * shaping its own.
 *
 * Renders nothing at all when there are no items and nothing went wrong, so a
 * page can include it unconditionally without leaving an empty heading behind.
 */
export default defineComponent({
    name: 'GalleryGrid',
    props: {
        department: { type: String, default: null },
        region: { type: String, default: null },
        general: { type: Boolean, default: false },
        heading: { type: String, default: 'Gallery' },
        sectionClass: { type: String, default: 'py-16 bg-slate-50' },
        // Lets a parent that already has the items (Region.vue gets them in
        // its own payload) skip the request entirely.
        preloaded: { type: Array, default: null },
    },
    setup(props) {
        const items = ref([])
        const loading = ref(false)
        const error = ref(null)

        const load = async () => {
            if (props.preloaded) {
                items.value = props.preloaded
                return
            }

            const params = new URLSearchParams()
            if (props.department) params.append('department', props.department)
            else if (props.region) params.append('region', props.region)
            else if (props.general) params.append('owner', 'general')

            loading.value = true
            error.value = null
            try {
                const res = await fetch(`/api/gallery?${params}`)
                const body = await res.json()
                if (body.success && body.data) {
                    items.value = body.data
                } else {
                    error.value = body.message || 'The gallery could not be loaded'
                }
            } catch (e) {
                // Surfaced, not swallowed: an empty grid and a failed request
                // are indistinguishable to a visitor otherwise.
                error.value = e.message || 'Failed to load the gallery'
            } finally {
                loading.value = false
            }
        }

        onMounted(load)
        watch(() => [props.department, props.region, props.general, props.preloaded], load)

        return { items, loading, error, load }
    }
})
</script>
