<template>
    <section class="py-16 bg-brand-green-100">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 v-if="data.heading" class="text-3xl font-bold text-brand-ink mb-6">{{ data.heading }}</h2>

            <!-- A real form, so Enter submits and the field is labelled. This
                 block holds no data of its own; it hands the query to the
                 locator, which owns the search. -->
            <form class="flex flex-col sm:flex-row gap-3" @submit.prevent="submit">
                <label class="sr-only" for="church-finder-query">{{ data.placeholder || 'Search' }}</label>
                <input
                    id="church-finder-query"
                    v-model="query"
                    type="search"
                    :placeholder="data.placeholder || 'Enter your town or suburb'"
                    class="flex-1 px-5 py-4 rounded-lg border border-brand-grey-200 focus:outline-none focus:ring-2 focus:ring-brand-green-700"
                >
                <button
                    type="submit"
                    class="px-6 py-4 rounded-lg bg-brand-green-700 text-white font-semibold hover:bg-brand-green-900 transition-colors"
                >{{ data.button_text || 'Find a church' }}</button>
            </form>
        </div>
    </section>
</template>

<script>
import { defineComponent, ref } from 'vue'
import { useRouter } from 'vue-router'

export default defineComponent({
    name: 'ChurchFinderBlock',
    props: { data: { type: Object, required: true } },
    setup() {
        const router = useRouter()
        const query = ref('')

        // Routed rather than posted: the locator reads its filters from the
        // URL, so this stays a link the user can bookmark and share.
        const submit = () => {
            router.push({
                path: '/find-church',
                query: query.value.trim() ? { search: query.value.trim() } : {},
            })
        }

        return { query, submit }
    },
})
</script>
