<template>
    <div id="app" class="min-h-screen flex flex-col">
        <Navbar />
        <main class="flex-grow">
            <!-- One breadcrumb for every route.
                 It used to live in CmsPage.vue, which is one of nine view
                 components the router points at, so 14 of 19 routes silently had
                 none and every new view inherited the gap by default. The trail
                 even broke mid-path: /departments/youth/sbq showed a breadcrumb
                 while its own parent did not.
                 No condition for the landing page is needed here — Breadcrumb
                 hides itself whenever the trail has a single entry, which is
                 true at "/" and nowhere else. -->
            <Breadcrumb v-if="!breadcrumbSuppressed" :current="currentPageTitle" />
            <router-view />
        </main>
        <Footer />
    </div>
</template>

<script>
import { defineComponent } from 'vue'
import Navbar from './components/Navbar.vue'
import Footer from './components/Footer.vue'
import Breadcrumb from './components/layout/Breadcrumb.vue'
import { currentPageTitle, breadcrumbSuppressed } from './composables/usePageMeta'

export default defineComponent({
    name: 'App',
    components: {
        Navbar,
        Footer,
        Breadcrumb
    },
    setup() {
        // Module-level refs from usePageMeta, which already owns the current
        // page's human title. Returned rather than imported in the template so
        // the dependency is visible at the component boundary.
        return { currentPageTitle, breadcrumbSuppressed }
    }
})
</script>
