<template>
    <!-- A list of announcements in the "Quiet frame" design (direction D3,
         chosen 2026-10-15): moved out of Department.vue on 2026-10-17 so the
         announcements block on CMS pages shows them the same way. Items have
         the department announcement shape (DepartmentController, and
         PageController for the block): title, content, image_path,
         image_alt, image_width, image_height, published_at. -->
    <div>
        <ol v-if="items.length">
            <li
                v-for="a in items"
                :key="a.id"
                class="border-t-2 border-dotted border-brand-grey-400 first:border-t-0 pt-8 pb-9 lg:grid lg:grid-cols-12 lg:gap-x-8 lg:grid-rows-[auto_auto_1fr] lg:pt-11 lg:pb-12"
            >
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.12em] text-brand-green-700 lg:col-start-1 lg:col-span-2 lg:row-start-1 lg:self-baseline lg:mb-0">
                    <time v-if="a.published_at" :datetime="a.published_at">{{ formatShortDate(a.published_at) }}</time>
                </p>
                <h3 :class="['text-[22px] leading-tight font-bold tracking-[-0.01em] text-brand-ink lg:row-start-1 lg:self-baseline lg:text-[28px] lg:leading-[1.2]', TITLE_COLUMNS[shape(a)]]">{{ a.title }}</h3>

                <figure v-if="a.image_path" :class="FIGURE_PLACES[shape(a)]">
                    <button
                        type="button"
                        class="group block w-full text-left focus:outline-none focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-[3px] focus-visible:outline-brand-clay-600"
                        @click="openAnnouncementPhoto(a)"
                    >
                        <!-- Never cropped: a flyer is a sheet with
                             its own edge, a photo is a print with a
                             white margin. The image's description
                             names the button for screen readers. -->
                        <img
                            :src="imageUrl(a.image_path)"
                            :alt="a.image_alt || ''"
                            :width="a.image_width || null"
                            :height="a.image_height || null"
                            loading="lazy"
                            decoding="async"
                            :class="PHOTO_FINISH[shape(a)]"
                        />
                        <span class="mt-3.5 inline-flex items-center gap-2 text-sm font-semibold text-brand-muted group-hover:text-brand-green-700 group-hover:underline underline-offset-[3px]" aria-hidden="true">
                            <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M2 6V2h4M10 2h4v4M14 10v4h-4M6 14H2v-4" /></svg>
                            View full size
                        </span>
                    </button>
                </figure>

                <div
                    v-if="a.content"
                    :class="['prose mt-4 max-w-[38rem] text-[17px] leading-[1.65] text-brand-ink lg:text-lg', MARKDOWN, BODY_PLACES[shape(a)]]"
                    v-html="renderMarkdown(a.content)"
                ></div>
            </li>
        </ol>

        <PhotoViewer
            v-model="announcementViewerOpen"
            :photos="announcementPhotos"
            :label="announcementViewerLabel"
            :counted="false"
        />
    </div>
</template>

<script>
import { defineComponent, ref } from 'vue'
import { renderMarkdown } from '../utils/markdown'
import { MARKDOWN_CLASSES } from '../utils/markdownClasses'
import PhotoViewer from './PhotoViewer.vue'

export default defineComponent({
    name: 'AnnouncementList',
    components: { PhotoViewer },
    props: {
        items: { type: Array, default: () => [] },
    },
    setup() {
        // "14 Oct 2026": the short date that heads each announcement. A plain
        // date (the announcements block stores 2026-10-14) is read as that
        // day here: new Date('2026-10-14') would be UTC midnight, which is
        // the day before anywhere west of Greenwich.
        const formatShortDate = (value) => {
            if (!value) return ''
            const plain = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
            const date = plain ? new Date(+plain[1], plain[2] - 1, +plain[3]) : new Date(value)
            return date.toLocaleDateString('en-NZ', { day: 'numeric', month: 'short', year: 'numeric' })
        }

        // An announcement's layout comes from its photo's shape (the API reads
        // the pixel size from the file). Unknown sizes use the landscape
        // layout, which still shows any photo whole.
        const shape = (a) => {
            if (!a.image_path) return 'text'
            return a.image_height > a.image_width ? 'portrait' : 'landscape'
        }

        // Literal class strings per shape: Tailwind reads source as text, so a
        // built-up class name would never be emitted.
        const TITLE_COLUMNS = {
            portrait: 'lg:col-start-3 lg:col-span-8',
            landscape: 'lg:col-start-3 lg:col-span-8',
            text: 'lg:col-start-3 lg:col-span-7',
        }
        const FIGURE_PLACES = {
            portrait: 'mt-5 max-w-[26rem] lg:max-w-none lg:col-start-3 lg:col-span-4 lg:row-start-2 lg:row-span-2 lg:mt-6',
            landscape: 'mt-5 lg:col-start-3 lg:col-span-10 lg:row-start-2 lg:mt-6',
            text: '',
        }
        const BODY_PLACES = {
            portrait: 'lg:col-start-7 lg:col-span-5 lg:row-start-2 lg:mt-6',
            landscape: 'lg:col-start-3 lg:col-span-7 lg:row-start-3 lg:mt-5',
            text: 'lg:col-start-3 lg:col-span-7 lg:row-start-2',
        }
        // A flyer is a sheet (its own edge and a low shadow); a photo is a
        // print (a thin white margin and a hairline).
        const PHOTO_FINISH = {
            portrait: 'block w-full h-auto shadow-[0_1px_2px_oklch(0.18_0.01_150/0.14),0_18px_34px_-22px_oklch(0.18_0.01_150/0.6)]',
            // Capped in height, still whole: a near-square phone photo laid
            // across ten columns would otherwise fill a whole screen.
            landscape: 'block w-auto max-w-full h-auto max-h-[36rem] bg-white p-2 ring-1 ring-brand-grey-200 shadow-[0_1px_2px_oklch(0.18_0.01_150/0.14),0_18px_34px_-22px_oklch(0.18_0.01_150/0.6)]',
            text: '',
        }

        const MARKDOWN = MARKDOWN_CLASSES

        const announcementViewerOpen = ref(false)
        const announcementPhotos = ref([])
        const announcementViewerLabel = ref('')

        const openAnnouncementPhoto = (a) => {
            announcementPhotos.value = [{
                src: imageUrl(a.image_path),
                alt: a.image_alt || '',
                caption: a.title,
                width: a.image_width,
                height: a.image_height,
            }]
            announcementViewerLabel.value = a.published_at ? `Announcement · ${formatShortDate(a.published_at)}` : 'Announcement'
            announcementViewerOpen.value = true
        }

        const imageUrl = (path) => {
            if (!path) return ''
            if (path.startsWith('http')) return path
            return `/storage/${path}`
        }

        return {
            formatShortDate, renderMarkdown, imageUrl,
            shape, TITLE_COLUMNS, FIGURE_PLACES, BODY_PLACES, PHOTO_FINISH, MARKDOWN,
            announcementViewerOpen, announcementPhotos, announcementViewerLabel, openAnnouncementPhoto,
        }
    },
})
</script>
