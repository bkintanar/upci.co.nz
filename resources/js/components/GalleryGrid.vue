<template>
    <!-- Direction D3, "Quiet frame": a batch of photos is a contact sheet. One
         white sheet on the paper ground, uniform numbered frames, and each frame
         is a button that opens the whole photo in the viewer. The number on a
         frame is the viewer's number: frame 4 is "Photo 4 of 14". Frames may crop
         to squares; the viewer always shows the whole photo. -->
    <section v-if="shouldRender" :class="sectionClass" :aria-labelledby="heading ? headingId : null">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <template v-if="heading">
                <div class="flex items-baseline justify-between gap-4 pb-3 border-b-[3px] border-brand-grey-200">
                    <h2 :id="headingId" class="text-[27px] md:text-[34px] leading-tight font-bold tracking-[-0.015em] text-brand-ink">{{ heading }}</h2>
                    <p v-if="items.length" class="text-base text-brand-muted tabular-nums whitespace-nowrap">{{ items.length === 1 ? '1 photo' : `${items.length} photos` }}</p>
                </div>
                <p v-if="items.length" class="mt-4 text-base text-brand-muted">Select a photo to see it whole.</p>
            </template>

            <BlockState
                :loading="loading"
                :error="error"
                :items="items"
                :empty-message="emptyMessage"
                :on-retry="load"
                empty-class="pt-6 text-base text-brand-muted"
            >
                <template #loading>Loading gallery…</template>

                <div class="mt-6 bg-white p-3 sm:p-6 ring-1 ring-brand-grey-200 shadow-[0_1px_2px_oklch(0.18_0.01_150/0.14),0_18px_34px_-22px_oklch(0.18_0.01_150/0.6)]">
                    <ol v-if="items.length > 1" class="grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-3 sm:gap-x-4 sm:gap-y-6 md:grid-cols-4 lg:grid-cols-5 lg:gap-x-6 lg:gap-y-7">
                        <li v-for="(item, i) in items" :key="item.id">
                            <button
                                type="button"
                                class="group block w-full text-left focus:outline-none focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-[3px] focus-visible:outline-brand-clay-600"
                                :aria-label="frameLabel(item, i)"
                                @click="openViewer(i)"
                            >
                                <span class="block aspect-square overflow-hidden bg-brand-grey-200">
                                    <img :src="item.image_url" alt="" loading="lazy" decoding="async" class="w-full h-full object-cover transition-opacity group-hover:opacity-90" />
                                </span>
                                <span class="mt-2 flex items-baseline gap-2 min-h-6 text-sm leading-snug">
                                    <span class="shrink-0 min-w-5 text-xs font-bold tracking-[0.04em] text-brand-muted tabular-nums">{{ i + 1 }}</span>
                                    <span v-if="item.title" class="font-medium text-brand-ink group-hover:underline underline-offset-[3px]">{{ item.title }}</span>
                                </span>
                            </button>
                        </li>
                    </ol>

                    <!-- One photo: the whole print, uncropped, instead of a
                         one-frame grid. -->
                    <button
                        v-else-if="items.length === 1"
                        type="button"
                        class="group inline-block max-w-full text-left focus:outline-none focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-[3px] focus-visible:outline-brand-clay-600"
                        :aria-label="frameLabel(items[0], 0)"
                        @click="openViewer(0)"
                    >
                        <img :src="items[0].image_url" alt="" loading="lazy" decoding="async" class="block w-auto max-w-full h-auto max-h-[34rem]" />
                        <span class="mt-2 flex items-baseline gap-2 min-h-6 text-sm leading-snug">
                            <span class="shrink-0 min-w-5 text-xs font-bold tracking-[0.04em] text-brand-muted tabular-nums">1</span>
                            <span v-if="items[0].title" class="font-medium text-brand-ink group-hover:underline underline-offset-[3px]">{{ items[0].title }}</span>
                        </span>
                    </button>
                </div>
            </BlockState>
        </div>

        <PhotoViewer
            v-model="viewerOpen"
            v-model:index="viewerIndex"
            :photos="viewerPhotos"
            :label="viewerLabel || heading || 'Gallery'"
        />
    </section>
</template>

<script>
import { defineComponent, computed, ref, watch, useId } from 'vue'
import BlockState from './BlockState.vue'
import PhotoViewer from './PhotoViewer.vue'
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
    components: { BlockState, PhotoViewer },
    props: {
        department: { type: String, default: null },
        region: { type: String, default: null },
        general: { type: Boolean, default: false },
        heading: { type: String, default: 'Gallery' },
        // Paper, like the rest of the photo pages; the white contact sheet sits
        // on it.
        sectionClass: { type: String, default: 'bg-brand-paper pt-16 pb-[88px] lg:pt-[88px]' },
        emptyMessage: { type: String, default: null },
        // Lets a parent that already has the items (Region.vue gets them in
        // its own payload) skip the request entirely.
        preloaded: { type: Array, default: null },
        // The small label above the photo in the viewer, e.g. the
        // department's name. Falls back to the heading.
        viewerLabel: { type: String, default: null },
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

        const headingId = useId()

        // Screen readers hear the number and the caption; the thumbnail image
        // itself is decorative inside its labelled button.
        const frameLabel = (item, i) => {
            const base = `Open photo ${i + 1} of ${items.value.length}`
            return item.title ? `${base}: ${item.title}` : base
        }

        const viewerOpen = ref(false)
        const viewerIndex = ref(0)

        const viewerPhotos = computed(() => items.value.map((item, i) => ({
            src: item.image_url,
            alt: item.title || item.description || `Photo ${i + 1} of ${items.value.length}`,
            caption: item.title || item.description || '',
        })))

        const openViewer = (i) => {
            viewerIndex.value = i
            viewerOpen.value = true
        }

        return {
            items, loading, error, load: fetched.load, shouldRender,
            headingId, frameLabel, viewerOpen, viewerIndex, viewerPhotos, openViewer,
        }
    },
})
</script>
