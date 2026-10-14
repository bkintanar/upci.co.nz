<template>
    <div>
        <div v-if="loading" class="text-center py-24">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-slate-600">Loading department...</p>
        </div>

        <div v-else-if="error" class="text-center py-24">
            <h1 class="text-3xl font-bold text-slate-900 mb-4">Department not found</h1>
            <p class="text-slate-600">{{ error }}</p>
            <router-link to="/departments" class="inline-block mt-6 text-brand-green-700 hover:underline">
                &larr; Back to Departments
            </router-link>
        </div>

        <template v-else-if="department">
            <section :class="heroClasses(department.color_theme)" class="relative text-white overflow-hidden">
                <div
                    v-if="department.hero_image"
                    class="absolute inset-0 opacity-20 bg-cover bg-center"
                    :style="{ backgroundImage: `url('${imageUrl(department.hero_image)}')` }"
                ></div>
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
                    <div class="text-center">
                        <!-- Requirement 1b: the department's own mark, falling
                             back to the site logo. Not all six departments have
                             a logo, and an absent one should read as the parent
                             brand rather than as a gap.

                             Sized by WIDTH, not height. This is the horizontal
                             2:1 lockup, so capping its height — as h-24/h-32 did
                             — leaves most of the hero's width unused and the mark
                             reads small however tall the box is. A width cap lets
                             it use the space a hero actually has.

                             drop-shadow-lg is gone: it existed to rescue contrast
                             that the light variant now provides. -->
                        <img
                            v-if="departmentLogo"
                            :src="departmentLogo"
                            :alt="`${department.name} logo`"
                            class="w-full max-w-sm sm:max-w-lg lg:max-w-2xl h-auto mx-auto mb-8"
                        />

                        <!-- The logo carries the department's name, so a visible
                             heading repeats it. Hidden rather than deleted: the
                             redundancy is visual, and the page still needs an h1
                             for its document outline and for screen readers.
                             Falls back to a visible heading when a department has
                             no logo — there, nothing else states the name. -->
                        <h1 :class="departmentLogo ? 'sr-only' : 'text-4xl md:text-5xl font-bold mb-6 leading-tight'">
                            {{ department.name }}
                        </h1>
                        <p v-if="department.scripture_quote" class="text-lg md:text-xl italic text-slate-100 max-w-3xl mx-auto whitespace-pre-line">
                            {{ department.scripture_quote }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Direction D3, "Quiet frame" (owner's pick, 2026-10-15): one left
                 axis (column 3 of 12) for every block of words, paper ground,
                 the photographs carry the warmth. -->
            <section v-if="department.description" class="bg-brand-paper pt-14 lg:pt-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 lg:grid lg:grid-cols-12 lg:gap-x-8">
                    <div :class="['prose max-w-2xl text-[19px] leading-[1.65] text-brand-ink lg:col-start-3 lg:col-span-8 lg:text-[22px] lg:leading-[1.6]', MARKDOWN]" v-html="renderMarkdown(department.description)"></div>
                </div>
            </section>

            <section class="bg-brand-paper pt-16 lg:pt-[88px]" aria-labelledby="announcements-heading">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="pb-3 border-b-[3px] border-brand-grey-200">
                        <h2 id="announcements-heading" class="text-[27px] md:text-[34px] leading-tight font-bold tracking-[-0.015em] text-brand-ink">Announcements</h2>
                    </div>

                    <!-- One vessel for every notice: the date, the words from
                         column 3, and at most one object. The object's own
                         shape picks the layout: a tall flyer stands beside its
                         words, a wide photo lies across the line, a notice with
                         no photo is only set type. The order is the same in
                         all three (date, title, photo, words), so on a phone
                         the photo always follows its title. -->
                    <ol v-if="department.announcements.length">
                        <li
                            v-for="a in department.announcements"
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
                    <p v-else class="pt-8 text-base text-brand-muted">No announcements yet. Check back soon.</p>
                </div>
            </section>

            <section class="bg-brand-paper pt-16 lg:pt-[88px]" aria-labelledby="calendar-heading">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="mb-8 pb-3 border-b-[3px] border-brand-grey-200">
                        <h2 id="calendar-heading" class="text-[27px] md:text-[34px] leading-tight font-bold tracking-[-0.015em] text-brand-ink">Calendar</h2>
                    </div>
                    <div v-if="department.events.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <article
                            v-for="e in department.events"
                            :key="e.id"
                            class="bg-white rounded-xl shadow-md border border-slate-200 p-6 flex flex-col"
                        >
                            <p class="text-brand-green-700 font-semibold text-sm mb-2">
                                {{ formatDate(e.start_date) }}<span v-if="e.end_date"> &ndash; {{ formatDate(e.end_date) }}</span>
                            </p>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">{{ e.name }}</h3>
                            <p v-if="e.description" class="text-slate-600 mb-4 line-clamp-3">{{ e.description }}</p>
                            <p v-if="e.location" class="text-sm text-slate-500 mb-2">{{ e.location }}</p>
                            <a
                                v-if="e.url"
                                :href="e.url"
                                target="_blank"
                                rel="noopener"
                                class="mt-auto text-brand-green-700 hover:underline text-sm font-medium"
                            >
                                Learn more &rarr;
                            </a>
                        </article>
                    </div>
                    <div v-else class="text-brand-muted">No upcoming events.</div>
                </div>
            </section>

            <!-- Requirement 2a: department gallery, through the shared grid.
                 Always shown, with "No photos yet." until the first upload
                 (owner, 2026-10-15), so visitors and admins know it exists. -->
            <GalleryGrid :department="department.slug" heading="Gallery" empty-message="No photos yet." :viewer-label="department.name" />

            <PhotoViewer
                v-model="announcementViewerOpen"
                :photos="announcementPhotos"
                :label="announcementViewerLabel"
                :counted="false"
            />
        </template>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { renderMarkdown } from '../utils/markdown'
import GalleryGrid from '../components/GalleryGrid.vue'
import PhotoViewer from '../components/PhotoViewer.vue'
import { useSiteSettings } from '../composables/useSiteSettings'
import { departmentHeroClasses } from '../utils/theme'
import { usePageMeta } from '../composables/usePageMeta'

export default defineComponent({
    name: 'Department',
    components: { GalleryGrid, PhotoViewer },
    setup() {
        const { setPageMeta } = usePageMeta()
        const { settings } = useSiteSettings()
        const route = useRoute()
        const department = ref(null)
        const loading = ref(true)
        const error = ref(null)

        const formatDate = (value) => {
            if (!value) return ''
            const d = new Date(value)
            return d.toLocaleDateString('en-NZ', { day: 'numeric', month: 'long', year: 'numeric' })
        }


        // "14 Oct 2026": the short date that heads each announcement.
        const formatShortDate = (value) => {
            if (!value) return ''
            return new Date(value).toLocaleDateString('en-NZ', { day: 'numeric', month: 'short', year: 'numeric' })
        }

        // Shared with any other surface that renders a department.
        const heroClasses = departmentHeroClasses

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
        // Markdown from the admin: there is no typography plugin, so
        // paragraphs, links and lists get their spacing and style here.
        // (`prose` stays on the container for the site's embed rules in
        // app.css.)
        // [overflow-wrap:anywhere]: a pasted link (the Men's description ends
        // with a long Facebook URL) wraps instead of widening a phone screen.
        const MARKDOWN = '[overflow-wrap:anywhere] space-y-2.5 [&_a]:text-brand-green-700 [&_a]:underline [&_a]:underline-offset-[3px] [&_strong]:font-semibold [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_blockquote]:italic'

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

        const fetchDepartment = async (slug) => {
            if (!slug) return
            loading.value = true
            error.value = null
            department.value = null
            try {
                const res = await fetch(`/api/departments/${slug}`, { headers: { Accept: 'application/json' } })
                const body = await res.json()
                if (body.success && body.data) {
                    department.value = body.data
                    setPageMeta(department.value.name, department.value.description)
                } else {
                    error.value = body.message || 'Department not found'
                }
            } catch (e) {
                error.value = e.message || 'Failed to load department'
            } finally {
                loading.value = false
            }
        }

        // Falls back to the site header logo when a department has none.
        // This hero is always a dark department hue ending in brand-ink, so the
        // LIGHT mark is the correct one here. The dark logo stays the fallback:
        // a department with no light variant still shows its own mark rather
        // than jumping straight to the generic site logo.
        //
        // The two card render sites — GetInvolved.vue and DepartmentListBlock.vue
        // — sit on white and deliberately keep reading `logo_path`. That is why
        // this preference lives here and not in the API.
        const departmentLogo = computed(() => {
            if (department.value?.logo_light_path) return imageUrl(department.value.logo_light_path)
            if (department.value?.logo_path) return imageUrl(department.value.logo_path)
            if (settings.value?.header_logo_url) return settings.value.header_logo_url
            return null
        })

        onMounted(() => fetchDepartment(route.params.slug))
        watch(() => route.params.slug, (slug) => {
            if (slug) fetchDepartment(slug)
        })

        return {
            department, loading, error, formatDate, formatShortDate, renderMarkdown, heroClasses, imageUrl, departmentLogo,
            shape, TITLE_COLUMNS, FIGURE_PLACES, BODY_PLACES, PHOTO_FINISH, MARKDOWN,
            announcementViewerOpen, announcementPhotos, announcementViewerLabel, openAnnouncementPhoto,
        }
    }
})
</script>
