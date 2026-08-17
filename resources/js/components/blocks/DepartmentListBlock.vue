<template>
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data.heading" class="text-3xl font-bold text-slate-900 mb-8">{{ data.heading }}</h2>

            <BlockState
                :loading="loading" :error="error" :items="visible"
                :empty-message="data.empty_message" :on-retry="load"
            >
                <template #loading>Loading departments…</template>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <router-link
                        v-for="department in visible"
                        :key="department.slug"
                        :to="`/departments/${department.slug}`"
                        class="group bg-white p-8 rounded-lg border border-slate-200 hover:border-brand-green-700 hover:shadow-lg transition-all flex flex-col no-underline"
                    >
                        <template v-if="data.show_logos !== false">
                            <img
                                v-if="department.logo_path"
                                :src="imageUrl(department.logo_path)"
                                :alt="`${department.name} logo`"
                                loading="lazy"
                                class="h-20 w-auto object-contain self-start mb-6"
                            />
                            <div
                                v-else
                                class="h-20 w-20 mb-6 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center text-2xl font-bold"
                            >{{ department.name.charAt(0) }}</div>
                        </template>

                        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-brand-green-700 transition-colors">
                            {{ department.name }}
                        </h3>

                        <span class="mt-auto text-brand-green-700 font-semibold text-sm">More info →</span>
                    </router-link>
                </div>
            </BlockState>
        </div>
    </section>
</template>

<script>
import { defineComponent, computed } from 'vue'
import BlockState from '../BlockState.vue'
import { useBlockData } from '../../composables/useBlockData'

export default defineComponent({
    name: 'DepartmentListBlock',
    components: { BlockState },
    props: { data: { type: Object, required: true } },
    setup(props) {
        const { items, loading, error, load } = useBlockData('/api/departments')

        const visible = computed(() => {
            const limit = parseInt(props.data.limit, 10)
            return Number.isFinite(limit) && limit > 0 ? items.value.slice(0, limit) : items.value
        })

        const imageUrl = (path) => (!path ? '' : path.startsWith('http') ? path : `/storage/${path}`)

        return { visible, loading, error, load, imageUrl }
    },
})
</script>
