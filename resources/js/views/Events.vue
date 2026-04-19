<template>
    <div class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-slate-900 mb-4">Events</h1>
                <p class="text-lg text-slate-600 max-w-3xl mx-auto">
                    General Conference, Annual Minister's Meeting, and other UPCI New Zealand events.
                </p>
            </div>

            <div v-if="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-slate-600">Loading events...</p>
            </div>

            <div v-else-if="error" class="text-center py-12 text-red-600">
                {{ error }}
            </div>

            <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="event in events"
                    :key="event.id"
                    class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden hover:shadow-lg transition-shadow"
                >
                    <div class="p-6">
                        <div class="text-sm font-medium text-blue-600 mb-1">
                            {{ formatDate(event.start_date) }}<template v-if="event.end_date"> – {{ formatDate(event.end_date) }}</template>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 mb-2">{{ event.name }}</h2>
                        <p v-if="event.description" class="text-slate-600 text-sm mb-4 line-clamp-3">{{ event.description }}</p>
                        <p v-if="event.location" class="text-slate-500 text-sm mb-2">{{ event.location }}</p>
                        <a
                            v-if="event.url"
                            :href="event.url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center text-blue-600 font-medium text-sm hover:underline"
                        >
                            Learn more
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <div v-if="!loading && !error && events.length === 0" class="text-center py-12 text-slate-500">
                No upcoming events at the moment. Check back soon.
            </div>

            <div class="mt-12 text-center">
                <router-link to="/calendar" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                    View calendar
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue'

export default defineComponent({
    name: 'Events',
    setup() {
        const events = ref([])
        const loading = ref(true)
        const error = ref(null)

        const formatDate = (dateStr) => {
            if (!dateStr) return ''
            const d = new Date(dateStr)
            return d.toLocaleDateString('en-NZ', { day: 'numeric', month: 'long', year: 'numeric' })
        }

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

        return { events, loading, error, formatDate }
    }
})
</script>
