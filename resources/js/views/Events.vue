<template>
    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h1 class="text-4xl font-bold text-slate-900 mb-3">Calendar of Events</h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    UPCI New Zealand — 2026 National Calendar. General Conference, Annual Ministers Meeting, department events, and more.
                </p>
            </div>

            <div v-if="loading" class="text-center py-20">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-slate-600">Loading events...</p>
            </div>

            <div v-else-if="error" class="text-center py-20 text-red-600">
                {{ error }}
            </div>

            <div v-else-if="!events.length" class="text-center py-20 text-slate-500">
                No upcoming events at the moment. Check back soon.
            </div>

            <div v-else class="space-y-10">
                <section v-for="group in groupedEvents" :key="group.monthKey">
                    <header
                        class="sticky top-28 z-10 bg-slate-50/95 backdrop-blur py-3 mb-4 border-b-2 border-slate-200 flex items-baseline gap-3"
                    >
                        <h2 class="text-sm font-bold uppercase tracking-widest text-slate-700">
                            {{ group.monthLabel }}
                        </h2>
                        <span class="text-xs text-slate-400">
                            {{ group.items.length }} event<template v-if="group.items.length !== 1">s</template>
                        </span>
                    </header>

                    <div class="space-y-4">
                        <article
                            v-for="event in group.items"
                            :key="event.id"
                            class="flex gap-4 sm:gap-6 rounded-xl shadow-sm border overflow-hidden transition-shadow hover:shadow-md"
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
                                    <span v-if="event.location" class="inline-flex items-center gap-1.5 text-slate-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ event.location }}
                                    </span>
                                </div>

                                <p v-if="event.description" class="mt-2 text-sm text-slate-600 line-clamp-2">
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
                                        class="ml-auto inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline"
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
                    class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors"
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

export default defineComponent({
    name: 'Events',
    setup() {
        const events = ref([])
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

        const groupedEvents = computed(() => {
            const groups = new Map()
            for (const ev of events.value) {
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

        return {
            events, loading, error,
            groupedEvents,
            statusClasses, deptChip, departmentLabel,
            dayNumber, monthAbbr, yearFrom,
            isMultiDay, sameMonth, formatDateRange,
        }
    },
})
</script>
