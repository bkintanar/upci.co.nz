<template>
    <!-- Direction E2, "wide agenda" (client-approved 2026-08-17). The page was a
         narrow centred column on a 1440 viewport, which left the calendar looking
         like a memo while two thirds of the screen sat empty. It now uses the
         page, and the month rule runs the full width so the year reads as a
         structure rather than a stack. -->
    <div class="py-12 bg-brand-paper min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Left-aligned, not centred: this is a reference document people
                 scan for a date, not a poster. -->
            <div class="mb-10 pb-6 border-b-2 border-brand-green-700">
                <h1 class="text-4xl md:text-5xl font-bold text-brand-ink mb-3">Calendar of Events</h1>
                <!-- The year was written into the copy, so this page would have
                     announced "2026" throughout 2027 and every year after. It is
                     read from the events themselves now, and omitted entirely
                     when there are none rather than stating a year on faith. -->
                <p class="text-lg text-brand-grey-600 max-w-3xl">
                    UPCI New Zealand<template v-if="calendarYears"> — {{ calendarYears }} National Calendar</template>.
                    General Conference, Annual Ministers Meeting, department events, and more.
                </p>
            </div>

            <!-- Scope filter. Rendered ONLY when more than one scope is actually
                 present in the data. Every one of the 49 events is currently
                 `national` with no region, so shipping a National/Regional
                 control today would offer a tab that is permanently empty — a
                 filter that finds nothing is worse than no filter. This appears
                 by itself the moment regional events exist. -->
            <div v-if="!loading && !error && scopes.length > 1" class="flex flex-wrap gap-2 mb-8">
                <button
                    v-for="s in scopes" :key="s.key" type="button"
                    @click="activeScope = s.key"
                    :class="activeScope === s.key
                        ? 'bg-brand-green-700 text-white border-brand-green-700'
                        : 'bg-white text-brand-ink border-brand-grey-200 hover:border-brand-green-700'"
                    class="px-4 py-2 text-sm font-semibold border transition-colors"
                >{{ s.label }} <span class="opacity-70">({{ s.count }})</span></button>
            </div>

            <div v-if="loading" class="text-center py-20">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-brand-green-700"></div>
                <p class="mt-4 text-brand-grey-600">Loading events...</p>
            </div>

            <div v-else-if="error" class="text-center py-20 text-brand-error">
                {{ error }}
            </div>

            <div v-else-if="!visibleEvents.length" class="text-center py-20 text-brand-grey-600">
                No upcoming events at the moment. Check back soon.
            </div>

            <div v-else class="space-y-10">
                <section v-for="group in groupedEvents" :key="group.monthKey">
                    <header
                        class="sticky top-28 z-10 bg-brand-paper/95 backdrop-blur py-3 mb-4 border-b-2 border-brand-grey-200 flex items-baseline gap-3"
                    >
                        <h2 class="text-sm font-bold uppercase tracking-widest text-brand-ink">
                            {{ group.monthLabel }}
                        </h2>
                        <span class="text-xs text-brand-grey-400">
                            {{ group.items.length }} event<template v-if="group.items.length !== 1">s</template>
                        </span>
                    </header>

                    <div class="space-y-4">
                        <article
                            v-for="event in group.items"
                            :key="event.id"
                            class="flex gap-4 sm:gap-6 border overflow-hidden transition-colors"
                            :class="statusClasses(event).card"
                        >
                            <!-- Date block -->
                            <div
                                class="flex-shrink-0 w-20 sm:w-24 flex flex-col items-center justify-center py-5 text-center"
                                :class="statusClasses(event).dateBlock"
                            >
                                <div class="text-2xl sm:text-3xl font-bold leading-none">
                                    {{ dayNumber(event.start_date) }}<template v-if="isMultiDay(event)">–{{ dayNumber(event.end_date) }}</template>
                                </div>
                                <div class="text-[10px] sm:text-xs uppercase tracking-widest mt-1 font-semibold">
                                    <template v-if="isMultiDay(event) && !sameMonth(event)">
                                        {{ monthAbbr(event.start_date) }}–{{ monthAbbr(event.end_date) }}
                                    </template>
                                    <template v-else>
                                        {{ monthAbbr(event.start_date) }}
                                    </template>
                                </div>
                                <div class="text-[10px] opacity-80 mt-0.5">{{ yearFrom(event.start_date) }}</div>
                            </div>

                            <!-- Event artwork, where it exists. The client confirmed
                                 flyers exist for most events; this is where they
                                 land. Hidden below `sm` so a phone gets the
                                 schedule rather than a column of thumbnails, and
                                 omitted entirely when there is no artwork — an
                                 event without a flyer is a normal event, so it
                                 gets no placeholder box pretending otherwise. -->
                            <div
                                v-if="event.image_path"
                                class="hidden sm:block flex-shrink-0 self-stretch w-28 lg:w-40 bg-brand-grey-200"
                            >
                                <img
                                    :src="imageUrl(event.image_path)" :alt="''" loading="lazy"
                                    class="w-full h-full object-cover"
                                >
                            </div>

                            <!-- Right content -->
                            <div class="flex-1 py-4 pr-4 sm:pr-6 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-lg sm:text-xl font-bold leading-snug" :class="statusClasses(event).title">
                                        {{ event.name }}
                                    </h3>
                                    <span
                                        v-if="statusClasses(event).pillLabel"
                                        :class="statusClasses(event).pill"
                                        class="flex-shrink-0 mt-1 whitespace-nowrap"
                                    >
                                        {{ statusClasses(event).pillLabel }}
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                    <span class="inline-flex items-center gap-1.5" :class="statusClasses(event).date">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ formatDateRange(event) }}
                                    </span>
                                    <span v-if="event.location" class="inline-flex items-center gap-1.5 text-brand-grey-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ event.location }}
                                    </span>
                                </div>

                                <p v-if="event.description" class="mt-2 text-sm text-brand-grey-600 line-clamp-2">
                                    {{ event.description }}
                                </p>

                                <div class="mt-3 flex items-center flex-wrap gap-2">
                                    <span
                                        v-if="event.department"
                                        :class="deptChip(event.department)"
                                        class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                    >
                                        {{ departmentLabel(event.department) }}
                                    </span>
                                    <a
                                        v-if="event.url"
                                        :href="event.url"
                                        target="_blank"
                                        rel="noopener"
                                        class="ml-auto inline-flex items-center text-sm font-semibold text-brand-green-700 hover:text-brand-green-900 hover:underline"
                                    >
                                        Details
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <div class="mt-16 text-center">
                <router-link
                    to="/calendar"
                    class="inline-flex items-center px-6 py-3 bg-brand-green-700 text-white font-semibold rounded-lg hover:bg-brand-green-900 transition-colors"
                >
                    View month calendar
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue'
import { getEventStatus, eventStatusClasses, departmentChipClasses } from '../utils/eventStatus'
import { usePageMeta } from '../composables/usePageMeta'

export default defineComponent({
    name: 'Events',
    setup() {
        const { setPageMeta } = usePageMeta()
        onMounted(() => setPageMeta('Calendar of Events', 'The UPCI New Zealand national calendar - conferences, ministers meetings and department events.'))

        const events = ref([])

        // One year reads "2026"; a span reads "2026-2027"; nothing reads as
        // nothing, because a calendar page with no events should not claim a
        // year it cannot support.
        const calendarYears = computed(() => {
            const years = [...new Set(
                events.value
                    .map((e) => String(e.start_date || '').slice(0, 4))
                    .filter((y) => /^\d{4}$/.test(y))
            )].sort()

            if (!years.length) return ''

            return years.length === 1 ? years[0] : `${years[0]}\u2013${years[years.length - 1]}`
        })
        const loading = ref(true)
        const error = ref(null)

        const parseDate = (s) => new Date(s + 'T00:00:00')

        const dayNumber = (s) => (s ? String(parseDate(s).getDate()).padStart(2, '0') : '')
        const monthAbbr = (s) => (s ? parseDate(s).toLocaleDateString('en-NZ', { month: 'short' }).toUpperCase() : '')
        const yearFrom  = (s) => (s ? parseDate(s).getFullYear() : '')
        const isMultiDay = (ev) => Boolean(ev.end_date && ev.end_date !== ev.start_date)
        const sameMonth = (ev) =>
            isMultiDay(ev) && parseDate(ev.start_date).getMonth() === parseDate(ev.end_date).getMonth()

        const formatDateRange = (ev) => {
            const d1 = parseDate(ev.start_date).toLocaleDateString('en-NZ', {
                day: 'numeric', month: 'long', year: 'numeric',
            })
            if (!isMultiDay(ev)) return d1
            const d2 = parseDate(ev.end_date).toLocaleDateString('en-NZ', {
                day: 'numeric', month: 'long', year: 'numeric',
            })
            return `${d1} – ${d2}`
        }

        // Matches CmsPage's resolver: the API publishes a bare disk-relative
        // path, so a stored value stays portable across domains and disks.
        const imageUrl = (path) => {
            if (!path) return ''
            if (path.startsWith('http')) return path
            return `/storage/${path}`
        }

        const activeScope = ref('all')

        // Built from the data, never hard-coded. The plan's anti-requirement
        // watch forbids hard-coded region names in resources/js, and a fixed
        // National/Regional pair would also be a lie whenever one side is empty.
        const scopes = computed(() => {
            const counts = new Map()
            for (const ev of events.value) {
                const key = ev.region?.slug || ev.scope || 'national'
                const label = ev.region?.name || (ev.scope === 'national' ? 'National' : ev.scope)
                if (!counts.has(key)) counts.set(key, { key, label, count: 0 })
                counts.get(key).count += 1
            }
            const list = [...counts.values()]

            // A lone scope needs no filter — the caller checks length > 1.
            return list.length > 1
                ? [{ key: 'all', label: 'All events', count: events.value.length }, ...list]
                : list
        })

        const visibleEvents = computed(() => {
            if (activeScope.value === 'all') return events.value
            return events.value.filter(
                (ev) => (ev.region?.slug || ev.scope || 'national') === activeScope.value
            )
        })

        const groupedEvents = computed(() => {
            const groups = new Map()
            for (const ev of visibleEvents.value) {
                if (!ev.start_date) continue
                const d = parseDate(ev.start_date)
                const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
                if (!groups.has(key)) {
                    const label = d.toLocaleDateString('en-NZ', { month: 'long', year: 'numeric' }).toUpperCase()
                    groups.set(key, { monthKey: key, monthLabel: label, items: [] })
                }
                groups.get(key).items.push(ev)
            }
            return [...groups.values()].sort((a, b) => a.monthKey.localeCompare(b.monthKey))
        })

        const statusClasses = (event) => eventStatusClasses(getEventStatus(event))
        const deptChip = (dept) => departmentChipClasses(dept?.color_theme)
        const departmentLabel = (dept) => dept?.name || 'General'

        const fetchEvents = async () => {
            try {
                const res = await fetch('/api/events')
                const data = await res.json()
                if (data.success && data.data) {
                    events.value = data.data
                } else {
                    error.value = 'Failed to load events'
                }
            } catch (e) {
                error.value = e.message || 'Failed to load events'
            } finally {
                loading.value = false
            }
        }

        onMounted(fetchEvents)

        // Everything the template touches must be listed here. A computed that
        // is declared and not returned is silently undefined in the template —
        // `groupedChurches` shipped exactly that way and left /find-church with
        // zero church cards through several commits, because lint, tests and
        // build all pass regardless.
        return {
            calendarYears,
            events, loading, error,
            groupedEvents, visibleEvents,
            scopes, activeScope,
            imageUrl,
            statusClasses, deptChip, departmentLabel,
            dayNumber, monthAbbr, yearFrom,
            isMultiDay, sameMonth, formatDateRange,
        }
    },
})
</script>
