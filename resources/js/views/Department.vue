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

            <section v-if="department.description" class="py-16 bg-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="prose prose-slate max-w-none text-slate-700" v-html="renderMarkdown(department.description)"></div>
                </div>
            </section>

            <section class="py-16 bg-slate-50">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-3xl font-bold text-slate-900 mb-8 text-center">Announcements</h2>
                    <div v-if="department.announcements.length" class="space-y-6">
                        <article
                            v-for="a in department.announcements"
                            :key="a.id"
                            class="bg-white rounded-xl shadow-md border border-slate-200 p-6"
                        >
                            <time v-if="a.published_at" class="text-sm text-slate-500 block mb-2">{{ formatDate(a.published_at) }}</time>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">{{ a.title }}</h3>
                            <div v-if="a.content" class="prose prose-slate max-w-none text-slate-700" v-html="renderMarkdown(a.content)"></div>
                        </article>
                    </div>
                    <div v-else class="text-center text-slate-500">No announcements yet. Check back soon.</div>
                </div>
            </section>

            <section class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-3xl font-bold text-slate-900 mb-8 text-center">Calendar</h2>
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
                    <div v-else class="text-center text-slate-500">No upcoming events.</div>
                </div>
            </section>
            <!-- Requirement 2a: department gallery, through the shared grid. -->
            <GalleryGrid :department="department.slug" heading="Gallery" />
        </template>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { renderMarkdown } from '../utils/markdown'
import GalleryGrid from '../components/GalleryGrid.vue'
import { useSiteSettings } from '../composables/useSiteSettings'
import { departmentHeroClasses } from '../utils/theme'
import { usePageMeta } from '../composables/usePageMeta'

export default defineComponent({
    name: 'Department',
    components: { GalleryGrid },
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


        // Shared with any other surface that renders a department.
        const heroClasses = departmentHeroClasses

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
                const res = await fetch(`/api/departments/${slug}`)
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

        return { department, loading, error, formatDate, renderMarkdown, heroClasses, imageUrl, departmentLogo }
    }
})
</script>
