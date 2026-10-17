<template>
    <!-- Announcements on a CMS page (owner request, 2026-10-17, for the
         Apostolic Bible College page). Set exactly like a department's
         Announcements section: the same section, heading and list
         (AnnouncementList). The items come from the block itself, made ready
         by PageController: hidden ones left out, newest first, photo sizes
         added. With nothing to show and no message to say so, the section
         is not rendered at all. On the homepage (2026-10-17) it keeps the
         homepage's rhythm: the same padding as the other paper sections,
         and no doubled gap between two of them (flushTop). -->
    <section v-if="items.length || data.empty_message" :class="['bg-brand-paper', sectionClasses]" :aria-labelledby="headingId">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="pb-3 border-b-[3px] border-brand-grey-200">
                <h2 :id="headingId" class="text-[27px] md:text-[34px] leading-tight font-bold tracking-[-0.015em] text-brand-ink">{{ data.heading || 'Announcements' }}</h2>
            </div>

            <AnnouncementList v-if="items.length" :items="items" />
            <p v-else class="pt-8 text-base text-brand-muted">{{ data.empty_message }}</p>
        </div>
    </section>
</template>

<script>
import { computed, defineComponent } from 'vue'
import AnnouncementList from '../AnnouncementList.vue'

let count = 0

export default defineComponent({
    name: 'AnnouncementsBlock',
    components: { AnnouncementList },
    props: {
        // heading, empty_message, items (see PageController::forPublic).
        data: { type: Object, required: true },
        // True when the block above is also on paper (CmsPage.flushTop).
        flushTop: { type: Boolean, default: false },
    },
    setup(props) {
        // Unique per block, in case a page carries two.
        const headingId = `announcements-block-${++count}`
        const items = computed(() => (Array.isArray(props.data.items) ? props.data.items : []))
        // The homepage sections' padding (FeatureBlock, EventsFeedBlock),
        // as literal strings for Tailwind.
        const sectionClasses = computed(() => (props.flushTop ? 'pb-14 md:pb-20 xl:pb-24' : 'py-14 md:py-20 xl:py-24'))

        return { headingId, items, sectionClasses }
    },
})
</script>
