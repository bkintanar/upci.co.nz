<template>
    <!-- Tiles layout (homepage, direction D2): each ministry in its own hue
         with its white mark, a title link, and its next date on the national
         calendar. -->
    <section v-if="data.layout === 'tiles'" :class="['bg-brand-paper', sectionClasses]" :aria-labelledby="headingId">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <SectionHead v-if="data.heading" :id="headingId" :heading="data.heading" :rule="false" />
            <DepartmentRibbon class="mt-3.5 mb-[18px]" />
            <p v-if="data.lede" class="mb-2 text-[17px] text-brand-muted">{{ data.lede }}</p>

            <BlockState
                :loading="loading" :error="error" :items="visible"
                :empty-message="data.empty_message" :on-retry="load"
            >
                <template #loading>Loading departments…</template>

                <ul class="grid lg:grid-cols-2 lg:gap-x-12">
                    <li
                        v-for="department in visible"
                        :key="department.slug"
                        class="group grid grid-cols-[132px_1fr] md:grid-cols-[240px_1fr] gap-4 md:gap-6 items-center py-4 md:py-5 border-b border-dotted border-brand-grey-400"
                    >
                        <!-- Repeats the title link for the pointer only. -->
                        <router-link
                            :to="`/departments/${department.slug}`"
                            tabindex="-1"
                            aria-hidden="true"
                            :class="['grid place-items-center aspect-[2/1] rounded-sm overflow-hidden', departmentHeroClasses(department.color_theme)]"
                        >
                            <img
                                v-if="department.logo_light_path"
                                :src="imageUrl(department.logo_light_path)"
                                alt=""
                                loading="lazy"
                                class="w-full h-full transition-transform duration-300 group-hover:scale-[1.04]"
                            />
                            <span v-else class="text-3xl font-bold text-white">{{ department.name.charAt(0) }}</span>
                        </router-link>
                        <div class="min-w-0">
                            <h3 class="text-[19px] md:text-[22px] leading-tight font-bold">
                                <router-link
                                    :to="`/departments/${department.slug}`"
                                    class="text-brand-green-700 hover:underline underline-offset-[3px]"
                                >{{ department.name }}</router-link>
                            </h3>
                            <p class="mt-1.5 text-[15px] leading-snug text-brand-muted">
                                <template v-if="nextEvent(department)">
                                    <span :class="['block mb-px text-xs font-bold tracking-[0.12em] uppercase', departmentTextClasses(department.color_theme)]">{{ nextTag(nextEvent(department)) }}</span>
                                    <MenuLink
                                        :item="{ url: nextEvent(department).url || '/events', open_in_new_tab: !!nextEvent(department).url }"
                                        class="font-semibold text-brand-ink underline decoration-2 decoration-brand-grey-400 underline-offset-4 hover:decoration-brand-ink"
                                    >{{ nextEvent(department).name }}</MenuLink>
                                </template>
                                <template v-else>
                                    <span class="block mb-px text-xs font-bold tracking-[0.12em] uppercase text-brand-muted">Calendar</span>
                                    No national dates listed yet
                                </template>
                            </p>
                        </div>
                    </li>
                </ul>
            </BlockState>
        </div>
    </section>

    <section v-else class="py-16 bg-white">
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
import { defineComponent, computed, useId } from 'vue'
import BlockState from '../BlockState.vue'
import MenuLink from '../MenuLink.vue'
import DepartmentRibbon from '../home/DepartmentRibbon.vue'
import SectionHead from '../home/SectionHead.vue'
import { useBlockData } from '../../composables/useBlockData'
import { departmentHeroClasses, departmentTextClasses } from '../../utils/theme'
import { runningDay, shortDate, todayIso } from '../../utils/eventDates'

export default defineComponent({
    name: 'DepartmentListBlock',
    components: { BlockState, MenuLink, DepartmentRibbon, SectionHead },
    props: {
        data: { type: Object, required: true },
        // Same-ground block above: its bottom padding already makes the gap.
        flushTop: { type: Boolean, default: false },
    },
    setup(props) {
        const { items, loading, error, load } = useBlockData('/api/departments')

        const visible = computed(() => {
            const limit = parseInt(props.data.limit, 10)
            return Number.isFinite(limit) && limit > 0 ? items.value.slice(0, limit) : items.value
        })

        const imageUrl = (path) => (!path ? '' : path.startsWith('http') ? path : `/storage/${path}`)

        const headingId = useId()
        const today = todayIso()
        const isTiles = computed(() => props.data.layout === 'tiles')
        const sectionClasses = computed(() => (props.flushTop ? 'pb-14 md:pb-20 xl:pb-24' : 'py-14 md:py-20 xl:py-24'))

        // One request for every row's "next" line, and only in the tiles
        // layout. The endpoint returns upcoming national events in date
        // order, so the first match per department is its next date.
        const { items: events } = useBlockData(() => (isTiles.value ? `/api/events?scope=national&from=${today}` : null))

        const nextByDepartment = computed(() => {
            const map = new Map()
            events.value.forEach((event) => {
                const slug = event.department?.slug
                if (slug && !map.has(slug)) map.set(slug, event)
            })
            return map
        })

        const nextEvent = (department) => nextByDepartment.value.get(department.slug) || null

        const nextTag = (event) => {
            if (runningDay(event, today)) return `On now · until ${shortDate(event.end_date)}`
            return `Next · ${shortDate(event.start_date)}`
        }

        return {
            visible, loading, error, load, imageUrl,
            headingId, sectionClasses, nextEvent, nextTag, departmentHeroClasses, departmentTextClasses,
        }
    },
})
</script>
