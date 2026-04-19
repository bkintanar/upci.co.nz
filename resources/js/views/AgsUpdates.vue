<template>
    <div class="py-16 bg-slate-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-slate-900 mb-4">Assistant General Superintendent's Updates</h1>
                <p class="text-lg text-slate-600">
                    News and updates from the Assistant General Superintendent.
                </p>
            </div>

            <div v-if="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-slate-600">Loading updates...</p>
            </div>

            <div v-else-if="error" class="text-center py-12 text-red-600">
                {{ error }}
            </div>

            <div v-else class="space-y-8">
                <article
                    v-for="update in updates"
                    :key="update.id"
                    class="bg-white rounded-xl shadow-md border border-slate-200 p-6"
                >
                    <time class="text-sm text-slate-500 block mb-2">{{ formatDate(update.published_at) }}</time>
                    <h2 class="text-2xl font-bold text-slate-900 mb-4">{{ update.title }}</h2>
                    <div class="prose prose-slate max-w-none text-slate-700" v-html="renderedContent(update.content)"></div>
                </article>
            </div>

            <div v-if="!loading && !error && updates.length === 0" class="text-center py-12 text-slate-500">
                No updates yet. Check back later.
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue'
import { marked } from 'marked'

export default defineComponent({
    name: 'AgsUpdates',
    setup() {
        const updates = ref([])
        const loading = ref(true)
        const error = ref(null)

        const formatDate = (dateStr) => {
            if (!dateStr) return ''
            const d = new Date(dateStr)
            return d.toLocaleDateString('en-NZ', { day: 'numeric', month: 'long', year: 'numeric' })
        }

        const renderedContent = (content) => {
            if (!content) return ''
            return marked.parse(content)
        }

        const fetchUpdates = async () => {
            try {
                const res = await fetch('/api/ags-updates')
                const data = await res.json()
                if (data.success && data.data) {
                    updates.value = data.data
                } else {
                    error.value = 'Failed to load updates'
                }
            } catch (e) {
                error.value = e.message || 'Failed to load updates'
            } finally {
                loading.value = false
            }
        }

        onMounted(fetchUpdates)

        return { updates, loading, error, formatDate, renderedContent }
    }
})
</script>
