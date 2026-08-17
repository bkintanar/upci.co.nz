<template>
    <nav v-if="crumbs.length > 1" class="border-b border-brand-grey-200" aria-label="Breadcrumb">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5">
            <ol class="flex flex-wrap items-center text-sm">
                <li
                    v-for="(crumb, index) in crumbs"
                    :key="crumb.path"
                    class="flex items-center"
                >
                    <!-- The separator belongs to the item that follows it, so it
                         never trails the last crumb. -->
                    <span v-if="index > 0" class="mx-2.5 text-brand-grey-400" aria-hidden="true">›</span>

                    <router-link
                        v-if="index < crumbs.length - 1"
                        :to="crumb.path"
                        class="text-brand-ink underline underline-offset-2 hover:text-brand-green-700 transition-colors"
                    >{{ crumb.label }}</router-link>

                    <!-- The current page is not a link, and says so to a screen
                         reader rather than only looking different. -->
                    <span v-else class="text-brand-grey-600" aria-current="page">{{ crumb.label }}</span>
                </li>
            </ol>
        </div>
    </nav>
</template>

<script>
import { defineComponent, computed } from 'vue'
import { useRoute } from 'vue-router'

/**
 * Ported from the approved B2 coverage screens (§13.1).
 *
 * Direction B as first drawn could not render any page that is not the
 * homepage: no breadcrumb, no title band, no in-page navigation. Its hero is a
 * task, not a title. These three are prerequisites for the rest of the rollout
 * rather than polish.
 *
 * The trail is derived from the route so every page gets one without the author
 * maintaining it. Intermediate segments are humanised from the slug; the leaf
 * uses the real page title, which is the one label a slug cannot supply
 * ("apostolic-bible-college" is not "Apostolic Bible College - UPCI NZ").
 */
export default defineComponent({
    name: 'Breadcrumb',
    props: {
        // The page's own title, used for the final crumb.
        current: { type: String, default: null },
    },
    setup(props) {
        const route = useRoute()

        const humanise = (segment) => segment
            .split('-')
            .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ')

        const crumbs = computed(() => {
            const segments = route.path.split('/').filter(Boolean)

            const trail = [{ path: '/', label: 'Home' }]

            segments.forEach((segment, index) => {
                const path = '/' + segments.slice(0, index + 1).join('/')
                const isLast = index === segments.length - 1

                trail.push({
                    path,
                    // Strip any site-name suffix the CMS title carries, or the
                    // crumb reads "Leadership - UPCI New Zealand".
                    label: isLast && props.current
                        ? props.current.split(/\s+[-|]\s+/)[0].trim()
                        : humanise(segment),
                })
            })

            return trail
        })

        return { crumbs }
    },
})
</script>
