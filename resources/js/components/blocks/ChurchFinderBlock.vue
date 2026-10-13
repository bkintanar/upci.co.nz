<template>
    <!-- Panel layout (homepage, direction D2): the finder with region links and
         a help box, and the congregations listed north to south beside it. -->
    <section v-if="data.layout === 'panel'" :class="['bg-brand-paper', sectionClasses]" :aria-labelledby="headingId">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div :class="showDirectory ? 'grid gap-14 lg:grid-cols-2 lg:gap-16 xl:grid-cols-[7fr_5fr] xl:gap-20' : ''">
                <div>
                    <SectionHead v-if="data.heading" :id="headingId" :heading="data.heading" :level="isFirst ? 'h1' : 'h2'" />
                    <p v-if="data.description" class="text-[17px] text-brand-muted">{{ data.description }}</p>

                    <form class="flex mt-5 max-w-[620px]" role="search" @submit.prevent="submit">
                        <label class="sr-only" :for="inputId">{{ data.placeholder || 'Search' }}</label>
                        <input
                            :id="inputId"
                            v-model="query"
                            type="search"
                            autocomplete="off"
                            :placeholder="data.placeholder || 'Enter your town or suburb'"
                            class="flex-1 min-w-0 h-14 px-4 border-2 border-r-0 border-brand-ink rounded-l-sm bg-brand-paper text-[17px] font-medium text-brand-ink placeholder:text-brand-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-clay-600"
                        >
                        <button
                            type="submit"
                            class="shrink-0 h-14 px-[22px] rounded-r-sm bg-brand-green-700 text-white text-sm font-bold tracking-[0.07em] uppercase hover:bg-brand-green-900 transition-colors"
                        >{{ data.button_text || 'Search' }}</button>
                    </form>

                    <template v-if="showRegions && regions.length">
                        <p class="mt-9 text-xs font-bold tracking-[0.12em] uppercase text-brand-muted">Or browse by region</p>
                        <ul class="mt-2.5 max-w-[620px] border-t border-brand-grey-200">
                            <li v-for="region in regions" :key="region.slug">
                                <router-link
                                    :to="`/regions/${region.slug}`"
                                    class="group flex items-center gap-3.5 py-4 px-1 border-b border-brand-grey-200"
                                >
                                    <span class="text-xl font-bold text-brand-green-700 group-hover:underline underline-offset-4">{{ region.name }}</span>
                                    <span v-if="region.churches_count != null" class="ml-auto text-[15px] text-brand-muted">
                                        <b class="inline-grid place-items-center min-w-[30px] h-[30px] mr-2 px-1 bg-brand-green-100 text-brand-green-900 font-extrabold rounded-sm">{{ region.churches_count }}</b>{{ region.churches_count === 1 ? 'congregation' : 'congregations' }}
                                    </span>
                                    <ArrowIcon kind="chevron" :class="['w-4 h-4 text-brand-green-700', region.churches_count == null ? 'ml-auto' : '']" />
                                </router-link>
                            </li>
                        </ul>
                    </template>

                    <div v-if="data.help_heading || data.help_text" class="mt-9 max-w-[620px] px-6 py-[22px] bg-brand-green-100 rounded-sm">
                        <p v-if="data.help_heading" class="text-[19px] font-bold leading-snug text-brand-green-900">{{ data.help_heading }}</p>
                        <p v-if="data.help_text" class="mt-1 text-base text-brand-ink">{{ data.help_text }}</p>
                        <MenuLink
                            v-if="data.help_link_text && data.help_link_url"
                            :item="{ url: data.help_link_url }"
                            class="mt-2.5 inline-flex items-center gap-2 text-[15px] font-bold text-brand-green-700 hover:underline underline-offset-4"
                        >{{ data.help_link_text }} <ArrowIcon /></MenuLink>
                    </div>
                </div>

                <div v-if="showDirectory">
                    <SectionHead :heading="data.directory_heading || 'Our congregations'" />
                    <BlockState
                        :loading="churchesLoading" :error="churchesError" :items="churches"
                        empty-message="Our church list is being updated. Please check back shortly."
                        :on-retry="loadChurches"
                    >
                        <template #loading>Loading churches…</template>
                        <ChurchSpine :groups="churchGroups" />
                    </BlockState>
                </div>
            </div>
        </div>
    </section>

    <section v-else class="py-16 bg-brand-green-100">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- h1 when this block opens the homepage, h2 otherwise. A page
                 needs exactly one h1, and under D2 the finder IS the homepage's
                 primary heading — there is no hero above it to carry one. -->
            <component
                :is="isFirst ? 'h1' : 'h2'"
                v-if="data.heading"
                :class="isFirst ? 'text-4xl md:text-5xl font-bold text-brand-ink mb-6' : 'text-3xl font-bold text-brand-ink mb-6'"
            >{{ data.heading }}</component>

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
import { computed, defineComponent, ref, useId } from 'vue'
import { useRouter } from 'vue-router'
import BlockState from '../BlockState.vue'
import MenuLink from '../MenuLink.vue'
import ArrowIcon from '../home/ArrowIcon.vue'
import ChurchSpine from '../home/ChurchSpine.vue'
import SectionHead from '../home/SectionHead.vue'
import { useBlockData } from '../../composables/useBlockData'
import { groupChurchesByRegion } from '../../utils/churches'

export default defineComponent({
    name: 'ChurchFinderBlock',
    components: { BlockState, MenuLink, ArrowIcon, ChurchSpine, SectionHead },
    props: {
        data: { type: Object, required: true },
        isFirst: { type: Boolean, default: false },
        // Same-ground block above: its bottom padding already makes the gap.
        flushTop: { type: Boolean, default: false },
    },
    setup(props) {
        const router = useRouter()
        const query = ref('')
        const headingId = useId()
        const inputId = useId()

        const isPanel = computed(() => props.data.layout === 'panel')
        const showRegions = computed(() => isPanel.value && props.data.show_regions !== false)
        const showDirectory = computed(() => isPanel.value && props.data.show_directory === true)

        const sectionClasses = computed(() => (props.flushTop ? 'pb-14 md:pb-20 xl:pb-24' : 'py-14 md:py-20 xl:py-24'))

        // A falsy URL fetches nothing, so the band layout costs no requests.
        // Counts come live from the API: a number typed into content would go
        // stale the day a church is added.
        const { items: regions } = useBlockData(() => (showRegions.value ? '/api/regions' : null))

        const {
            items: churches,
            loading: churchesLoading,
            error: churchesError,
            load: loadChurches,
        } = useBlockData(() => (showDirectory.value ? '/api/churches' : null))

        const churchGroups = computed(() => groupChurchesByRegion(churches.value))

        // Routed rather than posted, so the result is a link the visitor can
        // bookmark and share.
        //
        // The locator seeds its filters from these params in its own setup().
        // This comment used to assert that as though it were already true, and
        // it was not — the param was pushed and never read, so every search
        // from this box was silently discarded and returned the full list.
        const submit = () => {
            router.push({
                path: '/find-church',
                query: query.value.trim() ? { search: query.value.trim() } : {},
            })
        }

        return {
            query,
            submit,
            headingId,
            inputId,
            showRegions,
            showDirectory,
            sectionClasses,
            regions,
            churches,
            churchesLoading,
            churchesError,
            loadChurches,
            churchGroups,
        }
    },
})
</script>
