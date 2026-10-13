<template>
    <!-- Cards layout (homepage, direction D2): a sideways row of date-led
         tiles in each organiser's colour. Scroll-snap, no autoplay: the
         visitor moves it, by touch, trackpad or keyboard. -->
    <section v-if="data.layout === 'cards'" :class="['bg-brand-paper', sectionClasses]" :aria-labelledby="headingId">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <SectionHead
                :id="headingId"
                :heading="data.heading || 'Coming up'"
                :more-text="data.more_text"
                :more-url="data.more_url"
            />
            <ul v-if="visible.length" class="flex flex-wrap gap-x-[18px] gap-y-1.5 -mt-3 mb-6 text-[13px] text-brand-muted" aria-label="Colour key: who runs each event">
                <li v-for="entry in colourKey" :key="entry.key" class="inline-flex items-center gap-[7px] whitespace-nowrap">
                    <i :class="['w-3 h-3 rounded-sm', entry.swatch]" aria-hidden="true"></i>{{ entry.label }}
                </li>
            </ul>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <BlockState
                :loading="loading" :error="error" :items="visible"
                :empty-message="data.empty_message" :on-retry="load"
            >
                <template #loading>Loading events…</template>
            </BlockState>
        </div>

        <!-- Full-bleed so the row can run off the edge; the padding lines the
             first card up with the heading above at every width. -->
        <div
            v-if="!loading && !error && visible.length"
            class="overflow-x-auto snap-x snap-proximity pb-[18px] px-4 sm:px-6 lg:px-8 xl:px-[calc((100%_-_80rem)/2_+_2rem)] scroll-px-4 sm:scroll-px-6 lg:scroll-px-8 xl:scroll-px-[calc((100%_-_80rem)/2_+_2rem)] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-clay-600"
            role="region"
            :aria-label="`${data.heading || 'Upcoming events'} — scroll sideways for more`"
            tabindex="0"
        >
            <ol class="flex gap-5 md:gap-6 w-max">
                <li v-for="event in visible" :key="event.id" class="w-[272px] md:w-[304px] snap-start shrink-0">
                    <!-- The tile repeats the title link for the pointer; it is
                         hidden from the keyboard and screen readers so each
                         event is announced and tabbed to once. -->
                    <MenuLink
                        :item="{ url: eventUrl(event), open_in_new_tab: !!event.url }"
                        tabindex="-1"
                        aria-hidden="true"
                        :class="['relative block aspect-[2/1] overflow-hidden rounded-sm text-white no-underline', tileClasses(event)]"
                    >
                        <template v-if="event.image_path">
                            <img :src="imageUrl(event.image_path)" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover" />
                            <span class="absolute left-0 bottom-0 px-2.5 py-1.5 bg-brand-ink text-xs font-bold tracking-[0.1em] uppercase shadow-[inset_0_3px_0_theme(colors.brand.clay.600)]">{{ shortRange(event) }}</span>
                        </template>
                        <template v-else>
                            <span :class="['absolute left-4 top-3.5 text-xs font-bold tracking-[0.12em] uppercase', event.department ? 'text-white/90' : 'text-brand-clay-600']">{{ organiser(event) }}</span>
                            <img
                                v-if="event.department && event.department.logo_light_path"
                                :src="imageUrl(event.department.logo_light_path)"
                                alt=""
                                loading="lazy"
                                class="absolute right-2 top-1 w-[84px] h-[42px] opacity-95"
                            />
                            <img v-else-if="!event.department" :src="nationalLogo" alt="" loading="lazy" class="absolute right-3.5 top-3 h-[30px] w-auto" />
                            <span v-if="runningDay(event, today)" class="absolute left-4 bottom-16 px-2 py-1 bg-brand-paper text-brand-ink text-xs font-bold leading-tight rounded-sm">
                                On now · day {{ runningDay(event, today).day }} of {{ runningDay(event, today).of }}
                            </span>
                            <span class="absolute left-4 bottom-3 flex items-baseline gap-2 leading-none">
                                <b class="text-[44px] font-extrabold tracking-[-0.035em]">{{ tileDate(event).day }}</b>
                                <span class="text-sm font-bold tracking-[0.12em] uppercase">{{ tileDate(event).month }}</span>
                            </span>
                        </template>
                    </MenuLink>
                    <h3 class="mt-3.5 text-lg leading-snug font-bold">
                        <MenuLink
                            :item="{ url: eventUrl(event), open_in_new_tab: !!event.url }"
                            class="text-brand-green-700 hover:underline underline-offset-[3px]"
                        >{{ event.name }}</MenuLink>
                    </h3>
                    <p class="mt-1.5 text-[15px] leading-snug text-brand-muted">
                        <template v-if="event.location"><b class="font-semibold text-brand-ink">{{ event.location }}</b> · </template>{{ weekdayLine(event) }}
                    </p>
                </li>
            </ol>
        </div>
    </section>

    <section v-else class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data.heading" class="text-3xl font-bold text-slate-900 mb-8">{{ data.heading }}</h2>

            <BlockState
                :loading="loading" :error="error" :items="visible"
                :empty-message="data.empty_message" :on-retry="load"
            >
                <template #loading>Loading events…</template>

                <div class="space-y-4">
                    <article
                        v-for="event in visible"
                        :key="event.id"
                        class="flex items-start gap-6 bg-slate-50 rounded-xl p-6 border border-slate-200"
                    >
                        <div class="text-center shrink-0 w-16">
                            <div class="text-2xl font-bold text-brand-green-700">{{ dayOf(event.start_date) }}</div>
                            <div class="text-xs uppercase text-slate-500">{{ monthOf(event.start_date) }}</div>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-900 mb-1">{{ event.name }}</h3>
                            <p v-if="event.location" class="text-sm text-slate-500">{{ event.location }}</p>
                            <p v-if="event.region" class="text-xs text-slate-400 mt-1">{{ event.region.name }}</p>
                            <a
                                v-if="event.url" :href="event.url" target="_blank" rel="noopener"
                                class="text-sm text-brand-green-700 hover:underline font-medium"
                            >More info</a>
                        </div>
                    </article>
                </div>
            </BlockState>
        </div>
    </section>
</template>

<script>
import { defineComponent, computed, useId } from 'vue'
import BlockState from '../BlockState.vue'
import MenuLink from '../MenuLink.vue'
import SectionHead from '../home/SectionHead.vue'
import { useBlockData } from '../../composables/useBlockData'
import { DEPARTMENT_RIBBON, departmentHeroClasses, departmentSwatchClasses } from '../../utils/theme'
import { runningDay, shortRange, tileDate, todayIso, weekdayLine } from '../../utils/eventDates'
// The white mark, for national events' tiles on ink.
import nationalLogo from '../../../images/upci-nz-logo-footer.png'

const NATIONAL_TILE = 'bg-brand-ink shadow-[inset_0_3px_0_theme(colors.brand.clay.600)]'
const NATIONAL_SWATCH = 'bg-brand-ink shadow-[inset_0_2px_0_theme(colors.brand.clay.600)]'

// "National Men's Department" -> "Men's", "Prayer Ministry" -> "Prayer": the
// tile and the colour key have room for the distinguishing word only.
const shortDepartmentName = (name) => String(name || '')
    .replace(/^National\s+/i, '')
    .replace(/\s+(Department|Ministry|Ministries)$/i, '')

export default defineComponent({
    name: 'EventsFeedBlock',
    components: { BlockState, MenuLink, SectionHead },
    props: {
        data: { type: Object, required: true },
        // Same-ground block above: its bottom padding already makes the gap.
        flushTop: { type: Boolean, default: false },
    },
    setup(props) {
        const url = computed(() => {
            const params = new URLSearchParams()
            if (props.data.scope) params.append('scope', props.data.scope)
            if (props.data.region) params.append('region', props.data.region)
            if (props.data.department) params.append('department', props.data.department)
            // Filtered server-side so a page asking for upcoming events does not
            // download the whole 2026 calendar to discard most of it.
            //
            // Today in the visitor's LOCAL calendar. the ISO string is UTC, so
            // before midday in New Zealand it named yesterday and the feed
            // still listed an event that finished the day before.
            if (props.data.upcoming_only !== false) {
                const d = new Date(), pad = (n) => String(n).padStart(2, '0')
                params.append('from', `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`)
            }

            return `/api/events?${params}`
        })

        const { items, loading, error, load } = useBlockData(url)

        // Limit is applied here rather than in the query: the endpoint has no
        // limit parameter, and inventing one for a presentation concern would
        // put layout config into the API.
        const visible = computed(() => {
            const limit = parseInt(props.data.limit, 10)
            return Number.isFinite(limit) && limit > 0 ? items.value.slice(0, limit) : items.value
        })

        // Split as string parts, not through Date: "2026-09-01" parses as UTC
        // midnight and renders as 31 August in New Zealand.
        const dayOf = (d) => (d ? d.split('-')[2] : '')
        const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        const monthOf = (d) => (d ? MONTHS[parseInt(d.split('-')[1], 10) - 1] || '' : '')

        const headingId = useId()
        const today = todayIso()
        const sectionClasses = computed(() => (props.flushTop ? 'pb-14 md:pb-20 xl:pb-24' : 'py-14 md:py-20 xl:py-24'))

        const imageUrl = (path) => (!path ? '' : path.startsWith('http') ? path : `/storage/${path}`)

        // There is no event page of its own yet, so an event without a link
        // of its own opens the calendar.
        const eventUrl = (event) => event.url || '/events'

        const organiser = (event) => (event.department ? shortDepartmentName(event.department.name) : 'National')

        const tileClasses = (event) => (event.department ? departmentHeroClasses(event.department.color_theme) : NATIONAL_TILE)

        // Only the organisers actually in the row, national first, then the
        // departments in their own order.
        const colourKey = computed(() => {
            const entries = []

            if (visible.value.some((event) => !event.department)) {
                entries.push({ key: 'national', label: 'National', swatch: NATIONAL_SWATCH })
            }

            const departments = new Map()
            visible.value.forEach((event) => {
                if (event.department && !departments.has(event.department.slug)) {
                    departments.set(event.department.slug, event.department)
                }
            })

            const rank = (theme) => {
                const i = DEPARTMENT_RIBBON.indexOf(theme)
                return i === -1 ? DEPARTMENT_RIBBON.length : i
            }

            ;[...departments.values()]
                .sort((a, b) => rank(a.color_theme) - rank(b.color_theme))
                .forEach((department) => entries.push({
                    key: department.slug,
                    label: shortDepartmentName(department.name),
                    swatch: departmentSwatchClasses(department.color_theme),
                }))

            return entries
        })

        return {
            visible, loading, error, load, dayOf, monthOf,
            headingId, today, sectionClasses, imageUrl, eventUrl, organiser, tileClasses, colourKey, nationalLogo,
            runningDay, shortRange, tileDate, weekdayLine,
        }
    },
})
</script>
