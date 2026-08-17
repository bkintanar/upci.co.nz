<template>
    <footer class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <div class="flex items-center mb-6">
                        <img :src="footerLogo"
                             alt="UPCI New Zealand"
                             class="h-24 w-auto mr-6 drop-shadow-lg">
                    </div>
                    <p class="text-slate-300 mb-6 max-w-md leading-relaxed">
                        {{ blurb }}
                    </p>

                    <!-- Requirement 8: social links come from site settings.
                         These used to be three hard-coded icons all pointing at
                         "#" — two Twitter bird variants and a Pinterest, none of
                         them live. Removing the markup alone would have left the
                         same problem one edit away; driving it from settings
                         means there is no Twitter entry to render in the first
                         place. -->
                    <div v-if="socialLinks.length" class="flex space-x-4">
                        <a
                            v-for="link in socialLinks"
                            :key="link.platform"
                            :href="link.url"
                            target="_blank"
                            rel="noopener"
                            :aria-label="platformLabel(link.platform)"
                            class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-brand-green-700 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path :d="iconPath(link.platform)" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-6">Quick Links</h3>
                    <!-- Driven by the footer menu rows so the footer and header
                         cannot drift apart. Falls back to nothing rather than to
                         a stale hard-coded list. -->
                    <ul v-if="quickLinks.length" class="space-y-3">
                        <li v-for="link in quickLinks" :key="link.id">
                            <router-link :to="link.url" class="text-slate-300 hover:text-white transition-colors">
                                {{ link.label }}
                            </router-link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-6">Contact</h3>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-brand-green-100 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <div>
                                <p class="text-slate-300 text-sm">New Zealand</p>
                                <router-link to="/find-church" class="text-slate-400 text-xs hover:text-white transition-colors">
                                    Find your local church
                                </router-link>
                            </div>
                        </div>
                        <div v-if="contactEmail" class="flex items-start">
                            <svg class="w-5 h-5 text-brand-green-100 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <div>
                                <a :href="`mailto:${contactEmail}`" class="text-slate-300 text-sm hover:text-white transition-colors">
                                    {{ contactEmail }}
                                </a>
                                <p class="text-slate-400 text-xs">General inquiries</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800 mt-12 pt-8">
                <!-- The Privacy Policy / Terms / Cookie Policy links that sat
                     here were all href="#". No such pages exist, so they were
                     three dead links promising documents the site does not
                     have. Removed rather than left pointing nowhere. -->
                <p class="text-slate-400 text-sm text-center md:text-left">
                    &copy; {{ currentYear }} UPCI New Zealand. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>

<script>
import { defineComponent, computed, ref, onMounted } from 'vue'
// New UPCI NZ mark from the 2026 logo pack, white variant — both the
// navbar and footer sit on dark surfaces, and the standard mark sets its
// wordmark in black. PNG rather than SVG deliberately: the SVG export
// carries an unclassed full-canvas <rect> that renders as a black plate
// behind the mark. Trimmed from the pack's PNG, which has ~11% transparent
// padding per side. Uses variant 03, the only horizontal lockup in the pack
// (2.51:1); 01 and 02 are stacked and clip inside a horizontal bar.
// Bundled import as the fallback when site settings have no footer logo.
import upciLogo from '../../images/upci-nz-logo-footer.png'
import { useSiteSettings } from '../composables/useSiteSettings'

// Only the platforms the organisation actually uses. Adding a platform means
// adding its mark here — deliberately explicit, so an unrecognised platform
// renders no icon rather than a wrong one.
const ICONS = {
    facebook: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
    youtube: 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
    instagram: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z',
}

const DEFAULT_BLURB = 'Building strong Christian communities across New Zealand through faith, '
    + 'fellowship, and service. Join us in spreading the gospel and making a difference in our communities.'

export default defineComponent({
    name: 'Footer',
    setup() {
        const { settings } = useSiteSettings()
        const menuItems = ref([])

        const footerLogo = computed(() => settings.value?.footer_logo_url || upciLogo)
        const blurb = computed(() => settings.value?.footer_blurb || DEFAULT_BLURB)
        const contactEmail = computed(() => settings.value?.contact_email || null)

        // Only links with a mark we actually ship. An unknown platform is
        // skipped rather than rendered as an empty square.
        const socialLinks = computed(() =>
            (settings.value?.social_links || []).filter(link => link?.url && ICONS[link.platform])
        )

        const quickLinks = computed(() => menuItems.value)

        const iconPath = (platform) => ICONS[platform] || ''
        const platformLabel = (platform) => platform.charAt(0).toUpperCase() + platform.slice(1)

        onMounted(async () => {
            try {
                const res = await fetch('/api/menu/footer')
                const body = await res.json()
                if (body.success && body.data) menuItems.value = body.data
            } catch (e) {
                // A footer without quick links is degraded but usable; the
                // logo, blurb and contact block still render.
                menuItems.value = []
            }
        })

        const currentYear = computed(() => new Date().getFullYear())

        return { currentYear, footerLogo, blurb, contactEmail, socialLinks, quickLinks, iconPath, platformLabel }
    }
})
</script>
