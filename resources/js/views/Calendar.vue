<template>
    <div class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-slate-900 mb-2">Calendar</h1>
                <p class="text-lg text-slate-600">UPCI New Zealand events</p>
            </div>

            <div class="bg-white rounded-xl shadow-md border border-slate-200 p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <button
                        type="button"
                        @click="prevMonth"
                        class="p-2 rounded-lg hover:bg-slate-100 text-slate-600"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h2 class="text-xl font-bold text-slate-900">{{ monthLabel }}</h2>
                    <button
                        type="button"
                        @click="nextMonth"
                        class="p-2 rounded-lg hover:bg-slate-100 text-slate-600"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center text-sm font-medium text-slate-600 mb-2">
                    <div v-for="day in dayNames" :key="day">{{ day }}</div>
                </div>
                <div class="grid grid-cols-7 gap-1">
                    <div
                        v-for="cell in calendarCells"
                        :key="cell.key"
                        class="min-h-[80px] p-2 rounded-lg border border-slate-100 text-left"
                        :class="{ 'bg-slate-50': !cell.isCurrentMonth, 'bg-brand-green-100': cell.isToday }"
                    >
                        <span class="text-sm font-medium" :class="cell.isCurrentMonth ? 'text-slate-900' : 'text-slate-400'">
                            {{ cell.day }}
                        </span>
                        <div class="mt-1 space-y-1">
                            <div
                                v-for="ev in cell.events"
                                :key="ev.id"
                                class="text-xs px-2 py-1 rounded truncate"
                                :class="statusClasses(ev).chip"
                                :title="ev.name"
                            >
                                {{ ev.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-3">Events this month</h3>
                <ul v-if="eventsInMonth.length" class="space-y-2">
                    <li
                        v-for="ev in eventsInMonth"
                        :key="ev.id"
                        class="flex items-center gap-3 p-3 rounded-lg border"
                        :class="statusClasses(ev).card"
                    >
                        <span class="text-sm font-medium shrink-0" :class="statusClasses(ev).date">{{ formatDate(ev.start_date) }}</span>
                        <span class="font-medium" :class="statusClasses(ev).title">{{ ev.name }}</span>
                        <span
                            v-if="statusClasses(ev).pillLabel"
                            :class="statusClasses(ev).pill"
                        >
                            {{ statusClasses(ev).pillLabel }}
                        </span>
                        <a v-if="ev.url" :href="ev.url" target="_blank" rel="noopener" class="ml-auto text-brand-green-700 text-sm hover:underline">Details</a>
                    </li>
                </ul>
                <p v-else class="text-slate-500">No events this month.</p>
            </div>

            <div class="text-center">
                <router-link to="/events" class="inline-flex items-center text-brand-green-700 font-medium hover:underline">
                    View all events
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, computed, watch, onMounted } from 'vue'
import { getEventStatus, eventStatusClasses } from '../utils/eventStatus'
import { usePageMeta } from '../composables/usePageMeta'

const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

export default defineComponent({
    name: 'Calendar',
    setup() {
        const { setPageMeta } = usePageMeta()
        onMounted(() => setPageMeta('Month Calendar', 'UPCI New Zealand events shown month by month.'))

        const current = ref(new Date())
        const events = ref([])

        const monthLabel = computed(() => {
            return current.value.toLocaleDateString('en-NZ', { month: 'long', year: 'numeric' })
        })

        const dayNames = DAY_NAMES

        const calendarCells = computed(() => {
            const year = current.value.getFullYear()
            const month = current.value.getMonth()
            const first = new Date(year, month, 1)
            const last = new Date(year, month + 1, 0)
            const startPad = first.getDay()
            const daysInMonth = last.getDate()
            const totalCells = Math.ceil((startPad + daysInMonth) / 7) * 7
            const today = new Date()
            today.setHours(0, 0, 0, 0)

            const cells = []
            let dayNum = 1 - startPad
            for (let i = 0; i < totalCells; i++) {
                const d = new Date(year, month, dayNum)
                const dateKey = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')
                const dayEvents = events.value.filter(ev => {
                    const start = ev.start_date
                    const end = ev.end_date || ev.start_date
                    return dateKey >= start && dateKey <= end
                })
                cells.push({
                    key: i,
                    day: dayNum > 0 ? dayNum : '',
                    isCurrentMonth: d.getMonth() === month,
                    isToday: d.getTime() === today.getTime(),
                    events: dayEvents
                })
                dayNum++
            }
            return cells
        })

        const eventsInMonth = computed(() => {
            const year = current.value.getFullYear()
            const month = current.value.getMonth()
            const first = year + '-' + String(month + 1).padStart(2, '0') + '-01'
            const last = new Date(year, month + 1, 0)
            const lastStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(last.getDate()).padStart(2, '0')
            return events.value.filter(ev => {
                const end = ev.end_date || ev.start_date
                return ev.start_date <= lastStr && end >= first
            })
        })

        const formatDate = (dateStr) => {
            if (!dateStr) return ''
            const d = new Date(dateStr)
            return d.toLocaleDateString('en-NZ', { day: 'numeric', month: 'short', year: 'numeric' })
        }

        const prevMonth = () => {
            current.value = new Date(current.value.getFullYear(), current.value.getMonth() - 1)
        }
        const nextMonth = () => {
            current.value = new Date(current.value.getFullYear(), current.value.getMonth() + 1)
        }

        const fetchEvents = async () => {
            const year = current.value.getFullYear()
            const month = current.value.getMonth()
            const from = year + '-' + String(month).padStart(2, '0') + '-01'
            const to = year + '-' + String(month + 2).padStart(2, '0') + '-01'
            try {
                const res = await fetch(`/api/events?from=${from}&to=${to}`)
                const data = await res.json()
                if (data.success && data.data) {
                    events.value = data.data
                }
            } catch (_) {
                events.value = []
            }
        }

        watch(current, fetchEvents)
        onMounted(fetchEvents)

        const statusClasses = (event) => eventStatusClasses(getEventStatus(event))

        return {
            monthLabel,
            dayNames,
            calendarCells,
            eventsInMonth,
            formatDate,
            prevMonth,
            nextMonth,
            statusClasses
        }
    }
})
</script>
