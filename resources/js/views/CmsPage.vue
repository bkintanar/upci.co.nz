<template>
    <div v-if="loading" class="min-h-screen flex items-center justify-center">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Loading...</p>
        </div>
    </div>

    <!-- A missing page and a failed request are different problems and get
         different words. The emoji that used to head this is out: brand-spec.md
         §4 rules emoji icons out, and a sad face is the wrong register for a
         church telling someone they took a wrong turn. -->
    <div v-else-if="error" class="min-h-[60vh] flex items-center">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-green-700 mb-2">
                {{ isMissing ? 'Page not found' : 'Something went wrong' }}
            </p>

            <h1 class="text-3xl sm:text-4xl font-bold text-brand-ink mb-4 leading-tight">
                {{ isMissing ? 'We couldn\'t find that page.' : 'We couldn\'t load that page.' }}
            </h1>

            <p class="text-brand-grey-600 mb-8">
                <template v-if="isMissing">
                    It may have been moved or removed. These are the places people usually want.
                </template>
                <template v-else>
                    {{ error }} You could try again, or start from one of these.
                </template>
            </p>

            <!-- Onward links, not a lone "go home". Several URLs were retired
                 when the CMS scaffolding pages were unpublished, so someone can
                 land here holding a link that used to work. -->
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-8">
                <li v-for="link in notFoundLinks" :key="link.to">
                    <router-link
                        :to="link.to"
                        class="block border border-brand-grey-200 rounded-lg px-4 py-3 hover:border-brand-green-700 transition-colors"
                    >
                        <span class="font-semibold text-brand-ink">{{ link.label }}</span>
                        <span class="block text-sm text-brand-grey-600">{{ link.hint }}</span>
                    </router-link>
                </li>
            </ul>

            <button
                v-if="!isMissing"
                type="button"
                @click="fetchPage(getSlug())"
                class="inline-flex items-center px-5 py-3 font-semibold text-white bg-brand-green-700 hover:bg-brand-green-900 transition-colors rounded-lg"
            >Try again</button>
        </div>
    </div>

    <div v-else-if="page">
        <!-- Render content blocks -->
        <!-- §13.1: B could not render a non-homepage page. The breadcrumb hides
             itself at the root, and the title band only appears when the page
             has no hero of its own, so neither duplicates existing content. -->
        <Breadcrumb v-if="page" :current="page.title" />
        <PageHeader v-if="page && !hasHero && !isHome" :title="pageTitle" :lede="page.meta_description" />


        <div v-for="(block, index) in page.content" :key="index">
            <!-- Hero Section -->
            <section v-if="block.type === 'hero'" :class="getHeroClasses(block.data.style)" class="relative text-white overflow-hidden">
                <div v-if="block.data.background_image" class="absolute inset-0">
                    <img :src="getImageUrl(block.data.background_image)" alt="" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
                </div>
                <div v-else class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.1&quot;%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;1&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                </div>
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32 text-center">
                    <h1 class="text-5xl md:text-6xl font-bold mb-8 text-white leading-tight">{{ block.data.heading }}</h1>
                    <p v-if="block.data.subheading" class="text-xl md:text-2xl mb-12 text-slate-200 max-w-4xl mx-auto leading-relaxed">{{ block.data.subheading }}</p>

                    <!-- Buttons -->
                    <div v-if="block.data.button1_text || block.data.button2_text" class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a v-if="block.data.button1_text && block.data.button1_url"
                           :href="block.data.button1_url"
                           class="group bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <span class="flex items-center justify-center">
                                {{ block.data.button1_text }}
                                <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </span>
                        </a>
                        <a v-if="block.data.button2_text && block.data.button2_url"
                           :href="block.data.button2_url"
                           class="group border-2 border-white/30 text-white px-8 py-4 rounded-lg font-semibold hover:bg-white/10 hover:border-white/50 transition-all duration-300 backdrop-blur-sm">
                            <span class="flex items-center justify-center">
                                {{ block.data.button2_text }}
                                <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Scroll Indicator -->
                <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
                    <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
            </section>

            <!-- Text Block -->
            <section v-else-if="block.type === 'text'" :class="sectionBackground(block, 'py-20')">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 v-if="block.data.heading" :id="sectionId(block.data.heading)" class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">{{ block.data.heading }}</h2>
                        <div class="max-w-4xl mx-auto text-xl text-slate-600 leading-relaxed cms-text-content" :class="{ 'stats-content': isStatsBlock(block) }" :data-has-stats="isStatsBlock(block)" v-html="renderMarkdown(block.data.content)"></div>
                    </div>
                </div>
            </section>

            <!-- Image Block -->
            <section v-else-if="block.type === 'image'" class="py-12 bg-gray-50">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <figure>
                        <img :src="getImageUrl(block.data.image)" :alt="block.data.alt || ''" class="w-full rounded-lg shadow-lg" />
                        <figcaption v-if="block.data.caption" class="mt-4 text-center text-gray-600 italic">{{ block.data.caption }}</figcaption>
                    </figure>
                </div>
            </section>

            <!-- Two Column Layout -->
            <section v-else-if="block.type === 'two_column'" class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div :class="['grid grid-cols-1 gap-12 items-center mb-12', twoColumnGrid(block)]">
                        <div :class="['two-column-content', twoColumnSpan(block, 'left')]"
                             v-html="renderMarkdown(block.data.left_content)"></div>
                        <div :class="[
                                 'two-column-content',
                                 twoColumnSpan(block, 'right'),
                                 block.data.right_panel === false ? '' : 'bg-gray-100 p-8 rounded-lg'
                             ]"
                             v-html="renderMarkdown(block.data.right_content)"></div>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section v-else-if="block.type === 'cta'" :class="getCtaClasses(block.data.style)" class="py-16 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-6">{{ block.data.heading }}</h2>
                    <p v-if="block.data.text" class="text-xl mb-8 max-w-3xl mx-auto opacity-90">{{ block.data.text }}</p>
                    <a :href="block.data.button_url" class="inline-block bg-white text-gray-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                        {{ block.data.button_text }}
                    </a>
                </div>
            </section>

            <!-- Card Grid -->
            <section v-else-if="block.type === 'cards'"
                     :class="sectionBackground(block, 'py-16 lg:py-20')">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 v-if="block.data.heading" :id="sectionId(block.data.heading)" class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-10 lg:mb-12">{{ block.data.heading }}</h2>
                    <div :class="cardsGridClasses(block)">
                        <!-- variant === 'person' is an explicit author option, not
                             an inference from the heading or item count: portrait
                             crop, and the whole card opens a detail modal
                             (requirement 3). -->
                        <component
                            :is="card.data.variant === 'person' ? 'button' : 'div'"
                            v-for="(card, cardIndex) in block.data.items" :key="cardIndex"
                            :type="card.data.variant === 'person' ? 'button' : null"
                            :class="[
                                getMinistryCardClasses(card),
                                isRegistrationBlock(block) ? 'cms-registration-card' : '',
                                card.data.variant === 'person' ? 'text-left w-full cursor-pointer group focus:outline-none focus:ring-2 focus:ring-brand-green-700' : ''
                            ]"
                            @click="card.data.variant === 'person' ? openPerson(card.data) : null"
                        >
                            <!-- Portrait presentation, applied in the shared renderer
                                 rather than per page. object-cover with a fixed 3:4 box
                                 means a landscape or square source is cropped to
                                 portrait instead of letterboxed — two of the seven
                                 board photos are not portrait originals. object-top
                                 keeps heads in frame when the crop takes height. -->
                            <img v-if="card.data.variant === 'person' && card.data.icon"
                                 :src="getImageUrl(card.data.icon)" :alt="card.data.title"
                                 loading="lazy"
                                 class="w-full aspect-[3/4] mb-4 rounded-lg object-cover object-top group-hover:opacity-95 transition-opacity" />
                            <div v-else-if="card.data.icon_svg && card.data.icon_svg.includes('<svg')"
                                 :class="getCardIconContainerClass(card, cardIndex)"
                                 v-html="card.data.icon_svg">
                            </div>
                            <img v-else-if="card.data.icon" :src="getImageUrl(card.data.icon)" :alt="card.data.title"
                                 :class="card.data.icon_svg === 'blue-ministry' || card.data.icon_svg === 'green-ministry' ? 'w-full h-auto mx-auto mb-4 rounded-lg object-cover' : 'w-24 h-24 mx-auto mb-4 rounded-full object-cover'" />
                            <div :class="getMinistryCardContentClasses(card)">
                                <h3 :class="getMinistryCardTitleClasses(card)">{{ card.data.title }}</h3>
                                <p :class="getMinistryCardDescClasses(card)">{{ card.data.description }}</p>
                            </div>
                            <a v-if="card.data.link_url && card.data.variant !== 'person'" :href="card.data.link_url" :target="card.data.link_url.startsWith('http') ? '_blank' : '_self'" :rel="card.data.link_url.startsWith('http') ? 'noopener noreferrer' : null" :class="isRegistrationBlock(block) ? 'cms-registration-card-link' : ''" class="text-blue-600 hover:text-blue-800 font-semibold block text-center">
                                {{ card.data.link_text || 'Learn More' }} →
                            </a>
                            <span v-if="card.data.variant === 'person'"
                                  class="mt-2 block text-sm font-medium text-brand-green-700 group-hover:underline">
                                More info
                            </span>
                        </component>
                    </div>
                </div>
            </section>

            <!-- Data-bound blocks (§9). These render live data rather than
                 authored content, so the author sets configuration and the page
                 stays current on its own. Each handles its own loading, error
                 and empty states through BlockState. -->
            <ChurchFinderBlock v-else-if="block.type === 'church_finder'" :data="block.data" />
            <ChurchDirectoryBlock v-else-if="block.type === 'church_directory'" :data="block.data" />
            <EventsFeedBlock v-else-if="block.type === 'events_feed'" :data="block.data" />
            <DepartmentListBlock v-else-if="block.type === 'department_list'" :data="block.data" />
            <RegionListBlock v-else-if="block.type === 'region_list'" :data="block.data" />
            <GalleryBlock v-else-if="block.type === 'gallery'" :data="block.data" />
            <StatisticsBlock v-else-if="block.type === 'statistics'" :data="block.data" />

            <!-- Embed Code -->
            <section v-else-if="block.type === 'embed'" class="py-12 bg-white">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 v-if="block.data.title" class="text-3xl font-bold text-gray-900 mb-6">{{ block.data.title }}</h2>
                    <div v-html="block.data.code" class="embed-container"></div>
                </div>
            </section>
        </div>

        <!-- Requirement 3: leadership detail opens in place rather than
             navigating away. -->
        <Modal v-model="personOpen" :label="person ? person.title : 'Leadership detail'">
            <div v-if="person" class="p-6 sm:p-8">
                <div class="sm:flex sm:gap-8">
                    <img v-if="person.icon" :src="getImageUrl(person.icon)" :alt="person.title"
                         class="w-full sm:w-48 shrink-0 aspect-[3/4] rounded-lg object-cover object-top mb-6 sm:mb-0" />
                    <div>
                        <h2 class="text-2xl font-bold text-brand-ink mb-1">{{ person.title }}</h2>
                        <p v-if="person.description" class="text-brand-green-700 font-medium mb-4">
                            {{ person.description }}
                        </p>
                        <div v-if="person.bio" class="prose prose-slate max-w-none"
                             v-html="renderMarkdown(person.bio)"></div>
                        <!-- Said plainly rather than left blank: no biography
                             exists in the CMS for anyone yet, and an empty panel
                             reads as a loading failure. -->
                        <p v-else class="text-brand-grey-600 text-sm">
                            A fuller biography for {{ person.title }} has not been added yet.
                        </p>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script>
import { renderMarkdown } from '../utils/markdown';
import { computed, defineComponent, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import Modal from '../components/Modal.vue';
import Breadcrumb from '../components/layout/Breadcrumb.vue';
import PageHeader from '../components/layout/PageHeader.vue';
import ChurchFinderBlock from '../components/blocks/ChurchFinderBlock.vue';
import ChurchDirectoryBlock from '../components/blocks/ChurchDirectoryBlock.vue';
import EventsFeedBlock from '../components/blocks/EventsFeedBlock.vue';
import DepartmentListBlock from '../components/blocks/DepartmentListBlock.vue';
import RegionListBlock from '../components/blocks/RegionListBlock.vue';
import GalleryBlock from '../components/blocks/GalleryBlock.vue';
import StatisticsBlock from '../components/blocks/StatisticsBlock.vue';
import { usePageMeta } from '../composables/usePageMeta';

export default defineComponent({
    name: 'CmsPage',
    components: {
        Modal,
        Breadcrumb,
        PageHeader,
        ChurchFinderBlock,
        ChurchDirectoryBlock,
        EventsFeedBlock,
        DepartmentListBlock,
        RegionListBlock,
        GalleryBlock,
        StatisticsBlock,
    },
    setup() {
        const { setPageMeta } = usePageMeta()
        const route = useRoute()
        const page = ref(null)
        // A page with its own hero already states what it is; adding the
        // title band on top would say it twice.
        // The homepage is a TASK page in Direction B — its hero is the church
        // finder, not a title. A title band and a table of contents both belong
        // to documents, and neither suits the front door.
        const isHome = computed(() => route.path === '/')

        // A 404 and a network failure are not the same event. Saying "page not
        // found" when the request simply failed sends someone hunting for a
        // page that is actually still there.
        const isMissing = computed(() => /not found/i.test(error.value || ''))

        const notFoundLinks = [
            { to: '/find-church', label: 'Find a church', hint: 'Congregations across New Zealand' },
            { to: '/departments', label: 'Departments', hint: 'The national ministries' },
            { to: '/events', label: 'Calendar of events', hint: "What's on this year" },
            { to: '/connect-with-us', label: 'Connect with us', hint: 'Get in touch' },
        ]

        const hasHero = computed(() => (page.value?.content || []).some((b) => b.type === 'hero'))

        // The CMS titles carry a site-name suffix for the browser tab; the
        // on-page heading should not repeat it.
        const pageTitle = computed(() => (page.value?.title || '').split(/\s+[-|]\s+/)[0].trim())


        const contentsItems = computed(() =>
            (page.value?.content || [])
                .filter((block) => block.data?.heading)
                .map((block) => ({ href: `#${slugify(block.data.heading)}`, label: block.data.heading }))
        )

        // Section headings keep stable ids so a heading can be linked to
        // directly, even though the contents list that used them is gone.
        const sectionId = (heading) => String(heading || '')
            .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')

        const person = ref(null)
        const personOpen = ref(false)

        // Requirement 3: open the detail in place instead of navigating away.
        const openPerson = (data) => {
            person.value = data
            personOpen.value = true
        }
        const loading = ref(true)
        const error = ref(null)

        const fetchPage = async (slug) => {
            loading.value = true
            error.value = null

            try {
                const response = await fetch(`/api/pages/${slug}`)
                const data = await response.json()

                if (!data.success) {
                    throw new Error(data.message || 'Page not found')
                }

                page.value = data.data

                setPageMeta(page.value?.title, page.value?.meta_description)
            } catch (err) {
                error.value = err.message
                page.value = null
            } finally {
                loading.value = false
            }
        }

        const getImageUrl = (path) => {
            if (!path) return ''
            if (path.startsWith('http')) return path
            return `/storage/${path}`
        }

        const getHeroClasses = (style) => {
            const styles = {
                'gradient-slate': 'bg-gradient-to-br from-slate-800 via-slate-700 to-slate-900',
                'gradient-blue': 'bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800',
                'gradient-indigo': 'bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800',
                'gradient-purple': 'bg-gradient-to-br from-purple-600 via-purple-700 to-pink-800',
                'solid-blue': 'bg-blue-600',
                'solid-indigo': 'bg-indigo-600',
            }
            return styles[style] || styles['gradient-slate']
        }

        const getCtaClasses = (style) => {
            const styles = {
                'blue': 'bg-blue-600',
                'indigo': 'bg-indigo-600',
                'purple': 'bg-purple-600',
                'gray': 'bg-gray-700',
            }
            return styles[style] || styles['blue']
        }

        // Presentation is read from the block, never inferred. These five
        // rules used to derive appearance from array position, ordinal, item
        // count, or a substring of the prose — so an editor could not control
        // layout and reordering blocks silently restyled the page (§11.2).
        // The values were backfilled from what each rule produced, so existing
        // pages look exactly as they did.
        const BACKGROUNDS = {
            white: 'bg-white',
            slate: 'bg-slate-50',
        }

        const sectionBackground = (block, spacing) => {
            const background = BACKGROUNDS[block.data.background] || BACKGROUNDS.slate
            return `${spacing} ${background}`
        }

        // two_column widths. Literal strings for the same reason as the card
        // grids: Tailwind reads source as text, so `lg:grid-cols-${n}` is never
        // emitted and the layout silently collapses to one column.
        const TWO_COLUMN_GRIDS = {
            '1-1': 'lg:grid-cols-2',
            '2-1': 'lg:grid-cols-3',
            '1-2': 'lg:grid-cols-3',
        }

        const TWO_COLUMN_SPANS = {
            '2-1': { left: 'lg:col-span-2', right: '' },
            '1-2': { left: '', right: 'lg:col-span-2' },
        }

        const twoColumnGrid = (block) => TWO_COLUMN_GRIDS[block.data.ratio] || TWO_COLUMN_GRIDS['1-1']

        const twoColumnSpan = (block, side) => TWO_COLUMN_SPANS[block.data.ratio]?.[side] || ''

        const isStatsBlock = (block) => block.data.style === 'stats'

        const isRegistrationBlock = (block) => block.data.style === 'registration'

        // Literal class strings per column count — Tailwind's scanner reads
        // source as text, so a constructed `lg:grid-cols-${n}` is never emitted.
        const CARD_GRIDS = {
            2: 'grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8',
            3: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8',
            4: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8',
        }

        const cardsGridClasses = (block) => CARD_GRIDS[block.data.columns] || CARD_GRIDS[3]

        const getCardIconClass = (index, iconSvg) => {
            // Use green for checkmark icons
            if (iconSvg && iconSvg.includes('16.707 5.293')) {
                return 'bg-green-100'
            }
            // Default color rotation for other icons
            const colors = [
                'bg-blue-600',
                'bg-emerald-600',
                'bg-slate-600',
            ]
            return colors[index % colors.length]
        }

        const getCardIconContainerClass = (card, index) => {
            // Checkmark icons (small green circle)
            if (card.data.icon_svg && card.data.icon_svg.includes('16.707 5.293')) {
                return 'bg-green-100 w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-1'
            }
            // Ministry cards (no icon container)
            if (card.data.icon_svg === 'blue-ministry' || card.data.icon_svg === 'green-ministry') {
                return ''
            }
            // District leadership and other cards with icons (large blue circle for leadership icons)
            if (card.data.icon_svg && card.data.icon_svg.includes('stroke="currentColor"')) {
                return 'w-24 h-24 bg-blue-100 rounded-full mx-auto mb-4 flex items-center justify-center'
            }
            // Default card icons (colored rounded squares with hover effect)
            return [getCardIconClass(index, card.data.icon_svg), 'w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300'].join(' ')
        }

        const getMinistryCardClasses = (card) => {
            if (card.data.icon_svg === 'blue-ministry') {
                return 'bg-blue-50 p-6 rounded-lg'
            } else if (card.data.icon_svg === 'green-ministry') {
                return 'bg-green-50 p-6 rounded-lg'
            } else if (card.data.icon_svg && card.data.icon_svg.includes('16.707 5.293')) {
                return 'flex items-start space-x-3 bg-white p-6 rounded-lg'
            }
            return 'group bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 border border-slate-100'
        }

        const getMinistryCardContentClasses = (card) => {
            if (card.data.icon_svg === 'blue-ministry' || card.data.icon_svg === 'green-ministry') {
                return ''
            } else if (card.data.icon_svg && card.data.icon_svg.includes('16.707 5.293')) {
                return ''
            }
            return 'text-center'
        }

        const getMinistryCardTitleClasses = (card) => {
            if (card.data.icon_svg === 'blue-ministry') {
                return 'text-lg font-semibold text-blue-900 mb-3'
            } else if (card.data.icon_svg === 'green-ministry') {
                return 'text-lg font-semibold text-green-900 mb-3'
            } else if (card.data.icon_svg && card.data.icon_svg.includes('16.707 5.293')) {
                return 'font-semibold text-gray-900 mb-1'
            }
            return 'text-xl font-bold text-slate-900 mb-4'
        }

        const getMinistryCardDescClasses = (card) => {
            if (card.data.icon_svg === 'blue-ministry') {
                return 'text-blue-800 text-sm'
            } else if (card.data.icon_svg === 'green-ministry') {
                return 'text-green-800 text-sm'
            } else if (card.data.icon_svg && card.data.icon_svg.includes('16.707 5.293')) {
                return 'text-gray-600 text-sm'
            }
            return 'text-slate-600 leading-relaxed mb-4'
        }

        const getSlug = () => {
            // If slug param exists (from /cms/:slug), use it
            if (route.params.slug) {
                return route.params.slug
            }
            // Handle root path
            if (route.path === '/') {
                return 'home'
            }
            // Otherwise, derive from path (remove leading slash)
            return route.path.substring(1)
        }

        onMounted(() => {
            const slug = getSlug()
            if (slug) {
                fetchPage(slug)
            }
        })

        watch(() => route.path, () => {
            const slug = getSlug()
            if (slug) {
                fetchPage(slug)
            }
        })

        return {
            hasHero,
            isHome,
            isMissing,
            notFoundLinks,
            fetchPage,
            getSlug,
            sectionId,
            pageTitle,
            person,
            personOpen,
            openPerson,
            page,
            loading,
            error,
            renderMarkdown,
            getImageUrl,
            getHeroClasses,
            getCtaClasses,
            sectionBackground,
            isStatsBlock,
            twoColumnGrid,
            twoColumnSpan,
            getCardIconClass,
            getCardIconContainerClass,
            getMinistryCardClasses,
            getMinistryCardContentClasses,
            getMinistryCardTitleClasses,
            getMinistryCardDescClasses,
            cardsGridClasses,
            isRegistrationBlock,
        }
    }
})
</script>

<style scoped>
.cms-text-content {
    color: #475569;
    text-align: center;
}

.cms-text-content p {
    font-size: 1.25rem;
    line-height: 1.75;
    margin-bottom: 1rem;
}

/* First paragraph in Our Mission should be larger */
.cms-text-content > p:first-child {
    font-size: 1.25rem;
    line-height: 1.75;
    color: #64748b;
}

/* Stats content styling */
.cms-text-content.stats-content > p:first-child {
    font-size: 1.25rem;
    color: #64748b;
    margin-bottom: 3rem;
}

.cms-text-content h1 {
    font-size: 2.25rem;
    font-weight: 800;
    margin-bottom: 1rem;
    color: #0f172a;
}

.cms-text-content h2 {
    font-size: 1.875rem;
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #0f172a;
}

.cms-text-content h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1.5rem;
    color: #0f172a;
}

.cms-text-content p {
    margin-bottom: 1rem;
    line-height: 1.75;
}

.cms-text-content ul, .cms-text-content ol {
    margin-bottom: 1rem;
    padding-left: 0;
    list-style: none;
    text-align: center;
}

.cms-text-content li {
    margin-bottom: 1.5rem;
    font-size: 1.125rem;
    line-height: 1.5;
}

.cms-text-content strong {
    font-weight: 700 !important;
    font-size: 3rem !important;
    line-height: 1 !important;
    display: block !important;
    margin-bottom: 0.5rem !important;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    background-clip: text !important;
}

/* Alternate colors for stats */
.cms-text-content.stats-content li:nth-child(2) strong {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    background-clip: text !important;
}

.cms-text-content.stats-content li:nth-child(3) strong {
    background: linear-gradient(135deg, #475569 0%, #334155 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    background-clip: text !important;
}

.cms-text-content.stats-content li:nth-child(4) strong {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    background-clip: text !important;
}

.cms-text-content a {
    color: #2563eb;
    text-decoration: underline;
}

.cms-text-content a:hover {
    color: #1d4ed8;
}

.cms-text-content blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #64748b;
}

.cms-text-content hr {
    border: 0;
    height: 0;
    margin: 2rem 0;
    opacity: 0;
}

/* Grid layout for stats when using lists */
.cms-text-content ul {
    display: grid !important;
    grid-template-columns: repeat(1, 1fr) !important;
    gap: 3rem !important;
    margin-top: 3rem !important;
}

@media (min-width: 768px) {
    .cms-text-content.stats-content ul {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 2rem !important;
    }
}

.cms-text-content li {
    padding: 0 !important;
}

.cms-text-content.stats-content li {
    text-align: center !important;
}

.embed-container {
    position: relative;
    width: 100%;
}

.embed-container iframe {
    max-width: 100%;
}

/* Two column layout styling */
.two-column-content :deep(h2) {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: #1e293b !important;
    margin-bottom: 1rem !important;
    margin-top: 0 !important;
}

.two-column-content :deep(h3) {
    font-size: 1.25rem !important;
    font-weight: 600 !important;
    color: #1e293b !important;
    margin-bottom: 1rem !important;
    margin-top: 0 !important;
}

.two-column-content :deep(p) {
    color: #64748b !important;
    margin-bottom: 1rem !important;
    line-height: 1.75 !important;
    font-size: 1rem !important;
}

.two-column-content :deep(ul) {
    list-style: none !important;
    padding-left: 0 !important;
    margin-top: 0.5rem !important;
    margin-bottom: 0 !important;
}

.two-column-content :deep(li) {
    color: #64748b !important;
    margin-bottom: 0.5rem !important;
    position: relative;
    padding-left: 1.5rem !important;
    font-size: 1rem !important;
}

.two-column-content :deep(li::before) {
    content: "•";
    position: absolute;
    left: 0;
    color: #64748b;
    font-weight: bold;
}

.two-column-content :deep(blockquote) {
    border-left: 4px solid #3b82f6;
    padding-left: 1rem;
    margin: 1rem 0;
    font-style: italic;
    color: #64748b;
}

.two-column-content :deep(blockquote p) {
    margin-bottom: 0.5rem !important;
}

.two-column-content :deep(em) {
    font-size: 0.875rem;
    color: #94a3b8;
}

/* Registration / form-link cards: bigger, button-like, open external in new tab */
.cms-registration-card {
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.cms-registration-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}

.cms-registration-card .cms-registration-card-link {
    margin-top: auto;
    padding: 0.75rem 1.25rem;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none !important;
    border: 2px solid currentColor;
    transition: background 0.2s, color 0.2s;
}

.cms-registration-card.bg-blue-50 .cms-registration-card-link:hover {
    background: #1e40af;
    color: white;
    border-color: #1e40af;
}

.cms-registration-card.bg-green-50 .cms-registration-card-link:hover {
    background: #047857;
    color: white;
    border-color: #047857;
}

.cms-registration-card:not(.bg-blue-50):not(.bg-green-50) .cms-registration-card-link:hover {
    background: #1e293b;
    color: white;
    border-color: #1e293b;
}
</style>
