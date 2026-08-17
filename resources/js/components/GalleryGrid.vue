<template>
    <section v-if="shouldRender" :class="sectionClass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="heading" class="text-2xl font-bold text-slate-900 mb-8">{{ heading }}</h2>

            <BlockState
                :loading="loading"
                :error="error"
                :items="items"
                :empty-message="emptyMessage"
                :on-retry="load"
            >
                <template #loading>Loading gallery…</template>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
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
            </BlockState>
        </div>
    </section>
</template>

<script>
import { defineComponent, computed, ref, watch } from 'vue'
import BlockState from './BlockState.vue'
import { useBlockData } from '../composables/useBlockData'

/**
 * One gallery grid for departments, regions and the general gallery
 * (requirement 2). Pass exactly one of `department`, `region` or `general`;
 * the component reads the shared /api/gallery endpoint rather than each page
 * shaping its own.
 *
 * Loading, failure and genuine emptiness are handled by BlockState so all three
 * read differently — this component previously resolved a failed request to an
 * empty grid, which is indistinguishable from a gallery with nothing in it.
 */
export default defineComponent({
    name: 'GalleryGrid',
    components: { BlockState },
    props: {
        department: { type: String, default: null },
        region: { type: String, default: null },
        general: { type: Boolean, default: false },
        heading: { type: String, default: 'Gallery' },
        sectionClass: { type: String, default: 'py-16 bg-slate-50' },
        emptyMessage: { type: String, default: null },
        // Lets a parent that already has the items (Region.vue gets them in
        // its own payload) skip the request entirely.
        preloaded: { type: Array, default: null },
    },
    setup(props) {
        const url = computed(() => {
            if (props.preloaded) return null

            const params = new URLSearchParams()
            if (props.department) params.append('department', props.department)
            else if (props.region) params.append('region', props.region)
            else if (props.general) params.append('owner', 'general')

            return `/api/gallery?${params}`
        })

        const fetched = useBlockData(url, { immediate: !props.preloaded })

        // Preloaded items bypass the fetch entirely but still flow through the
        // same rendering path, so a page passing an empty array gets the empty
        // state rather than a bare heading.
        const preloadedItems = ref(props.preloaded || [])
        watch(() => props.preloaded, (value) => { preloadedItems.value = value || [] })

        const items = computed(() => (props.preloaded ? preloadedItems.value : fetched.items.value))
        const loading = computed(() => (props.preloaded ? false : fetched.loading.value))
        const error = computed(() => (props.preloaded ? null : fetched.error.value))

        // A section with nothing to say and nothing wrong stays out of the
        // page, so a parent can include it unconditionally. An authored empty
        // message means the author WANTS it said, so it renders.
        const shouldRender = computed(() =>
            loading.value || error.value || items.value.length > 0 || !!props.emptyMessage
        )

        return { items, loading, error, load: fetched.load, shouldRender }
    },
})
</script>
