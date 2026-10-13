<template>
    <!-- Direction D1's bar (owner's choice, 2026-10-14: "the menu from D1,
         where I can clearly see it"): brand green-900 with plain white links,
         an underline on hover, and the log-in as an outlined pill. Stays h-20
         (80px): the ABC enrolment page's running head is pinned under it with
         --sticky-top: 80px. -->
    <nav class="bg-brand-green-900 text-white sticky top-0 z-50" aria-label="Main">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20 gap-5">
                <router-link to="/" class="shrink-0 flex items-center">
                    <img :src="headerLogo" alt="UPCI New Zealand — home" class="h-14 xl:h-[62px] w-auto">
                </router-link>

                <ul v-if="!loading" class="hidden xl:flex items-center gap-[clamp(14px,1.45vw,22px)] text-[14.5px] font-semibold">
                    <li v-for="item in menuItems" :key="item.id" :class="hasChildren(item) ? 'relative group' : ''">
                        <!-- Menu item with dropdown. Opens on hover and on keyboard
                             focus (focus-within), so tabbing reaches the children. -->
                        <template v-if="hasChildren(item)">
                            <router-link
                                v-if="item.url && !isPlainAnchor(item.url)"
                                :to="item.url"
                                class="inline-flex items-center gap-1.5 py-2 whitespace-nowrap hover:underline decoration-2 underline-offset-[7px]">
                                {{ item.label }}
                                <span class="w-0 h-0 border-x-4 border-x-transparent border-t-[5px] border-t-current" aria-hidden="true"></span>
                            </router-link>
                            <button v-else type="button" class="inline-flex items-center gap-1.5 py-2 whitespace-nowrap font-semibold hover:underline decoration-2 underline-offset-[7px]">
                                {{ item.label }}
                                <span class="w-0 h-0 border-x-4 border-x-transparent border-t-[5px] border-t-current" aria-hidden="true"></span>
                            </button>
                            <!-- pt-2 instead of a margin: the gap belongs to the
                                 panel, so the pointer never crosses dead space
                                 and drops the hover on its way down. -->
                            <div class="absolute left-0 top-full pt-2 w-72 opacity-0 invisible group-hover:opacity-100 group-hover:visible group-focus-within:opacity-100 group-focus-within:visible transition-all duration-200 z-50">
                                <div class="py-2 bg-white text-brand-ink rounded-sm shadow-xl border-t-[3px] border-brand-clay-600">
                                    <template v-for="child in item.children" :key="child.id">
                                        <a
                                            v-if="child.url && isPlainAnchor(child.url)"
                                            :href="child.url"
                                            :target="child.open_in_new_tab ? '_blank' : '_self'"
                                            class="block px-6 py-3 text-sm hover:bg-brand-green-100 hover:text-brand-green-900 transition-colors">
                                            <div class="font-semibold">{{ child.label }}</div>
                                            <div v-if="child.description" class="text-xs font-normal text-brand-muted">{{ child.description }}</div>
                                        </a>
                                        <router-link
                                            v-else-if="child.url"
                                            :to="child.url"
                                            class="block px-6 py-3 text-sm hover:bg-brand-green-100 hover:text-brand-green-900 transition-colors">
                                            <div class="font-semibold">{{ child.label }}</div>
                                            <div v-if="child.description" class="text-xs font-normal text-brand-muted">{{ child.description }}</div>
                                        </router-link>

                                        <!-- Third level, e.g. SBQ under Youth Ministry.
                                             Indented inside the same panel rather than a
                                             hover flyout: a nested flyout needs the pointer
                                             to cross a gap without dropping the hover, and
                                             has no touch equivalent at all. -->
                                        <!-- MenuLink so an external grandchild URL leaves
                                             the site instead of becoming an in-app path. -->
                                        <MenuLink
                                            v-for="grandchild in (child.children || [])"
                                            :key="grandchild.id"
                                            :item="grandchild"
                                            class="flex items-center pl-12 pr-6 py-2 text-sm font-medium text-brand-muted hover:bg-brand-green-100 hover:text-brand-green-900 transition-colors">
                                            <span class="w-3 border-t border-brand-grey-400 mr-3" aria-hidden="true"></span>
                                            {{ grandchild.label }}
                                        </MenuLink>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Minister's Log-in: an outlined pill, set apart from
                             the public destinations. -->
                        <MenuLink
                            v-else-if="isLogin(item)"
                            :item="item"
                            class="inline-flex items-center px-4 py-2 whitespace-nowrap border-[1.5px] border-white rounded-full hover:bg-white hover:text-brand-green-900 transition-colors">
                            {{ item.label }}
                        </MenuLink>

                        <!-- Regular menu item (no dropdown) -->
                        <MenuLink
                            v-else
                            :item="item"
                            class="inline-flex items-center py-2 whitespace-nowrap hover:underline decoration-2 underline-offset-[7px]">
                            {{ item.label }}
                        </MenuLink>
                    </li>
                </ul>

                <!-- Mobile menu button: a labelled pill, not a bare icon. -->
                <button
                    type="button"
                    class="xl:hidden inline-flex items-center gap-2.5 min-h-11 px-4 rounded-full border-[1.5px] border-white font-bold hover:bg-white hover:text-brand-green-900 transition-colors"
                    :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
                    aria-controls="mobile-menu"
                    @click="toggleMobileMenu">
                    <svg class="w-[18px] h-[14px]" viewBox="0 0 18 14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path v-if="!mobileMenuOpen" d="M0 1h18M0 7h18M0 13h18" />
                        <path v-else d="M3 1l12 12M15 1L3 13" />
                    </svg>
                    {{ mobileMenuOpen ? 'Close' : 'Menu' }}
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div v-show="mobileMenuOpen" id="mobile-menu" class="xl:hidden bg-brand-green-900 border-t border-white/15">
            <div v-if="!loading" class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-6 space-y-1">
                <template v-for="item in menuItems" :key="item.id">
                    <!-- Parent with children -->
                    <div v-if="hasChildren(item)" class="space-y-1">
                        <router-link
                            v-if="item.url && !isPlainAnchor(item.url)"
                            :to="item.url"
                            @click="mobileMenuOpen = false"
                            class="block px-3 py-2 text-base font-semibold text-white hover:bg-white/10 rounded-sm transition-colors">
                            {{ item.label }}
                        </router-link>
                        <div v-else class="px-3 pt-3 pb-1 text-xs font-bold tracking-[0.12em] uppercase text-white/70">{{ item.label }}</div>
                        <template v-for="child in item.children" :key="child.id">
                            <a
                                v-if="child.url && isPlainAnchor(child.url)"
                                :href="child.url"
                                :target="child.open_in_new_tab ? '_blank' : '_self'"
                                @click="mobileMenuOpen = false"
                                class="block px-6 py-2.5 text-base text-white hover:bg-white/10 rounded-sm transition-colors">
                                <div class="font-semibold">{{ child.label }}</div>
                                <div v-if="child.description" class="text-sm text-white/75">{{ child.description }}</div>
                            </a>
                            <router-link
                                v-else-if="child.url"
                                :to="child.url"
                                @click="mobileMenuOpen = false"
                                class="block px-6 py-2.5 text-base text-white hover:bg-white/10 rounded-sm transition-colors">
                                <div class="font-semibold">{{ child.label }}</div>
                                <div v-if="child.description" class="text-sm text-white/75">{{ child.description }}</div>
                            </router-link>

                            <!-- Third level, indented. Mobile needs this as much as
                                 desktop: without it a grandchild is unreachable on a
                                 phone entirely. -->
                            <MenuLink
                                v-for="grandchild in (child.children || [])"
                                :key="grandchild.id"
                                :item="grandchild"
                                @click="mobileMenuOpen = false"
                                class="flex items-center pl-12 pr-6 py-2 text-base text-white/90 hover:bg-white/10 rounded-sm transition-colors">
                                <span class="w-3 border-t border-white/40 mr-3" aria-hidden="true"></span>
                                {{ grandchild.label }}
                            </MenuLink>
                        </template>
                    </div>

                    <MenuLink
                        v-else-if="isLogin(item)"
                        :item="item"
                        @click="mobileMenuOpen = false"
                        class="inline-flex items-center mt-3 ml-3 px-4 py-2 text-base font-semibold border-[1.5px] border-white rounded-full hover:bg-white hover:text-brand-green-900 transition-colors">
                        {{ item.label }}
                    </MenuLink>

                    <!-- Regular item (no children) -->
                    <MenuLink
                        v-else
                        :item="item"
                        @click="mobileMenuOpen = false"
                        class="block px-3 py-2.5 text-base font-semibold text-white hover:bg-white/10 rounded-sm transition-colors">
                        {{ item.label }}
                    </MenuLink>
                </template>
            </div>
        </div>
    </nav>
</template>

<script>
import axios from 'axios';
import { computed, defineComponent, onMounted, ref } from 'vue';
// New UPCI NZ mark from the 2026 logo pack, white variant — both the
// navbar and footer sit on dark surfaces, and the standard mark sets its
// wordmark in black. PNG rather than SVG deliberately: the SVG export
// carries an unclassed full-canvas <rect> that renders as a black plate
// behind the mark. Trimmed from the pack's PNG, which has ~11% transparent
// padding per side. Uses variant 03, the only horizontal lockup in the pack
// (2.51:1); 01 and 02 are stacked and clip inside a horizontal bar.
// Still a build-time import —
// making this CMS-editable is the site-settings work (T9-T11).
import upciLogo from '../../images/upci-nz-logo-nav.png';
import { useSiteSettings } from '../composables/useSiteSettings';
import MenuLink from './MenuLink.vue';
import { isPlainAnchor } from '../utils/menuUrl';

// Used only when /api/menu/header fails. Hard-coded on purpose: a fallback
// that depends on the thing that just failed is not a fallback. Kept to the
// routes that are stable and top-level, so it cannot drift far from the real
// menu — anything deeper belongs in the CMS.
const FALLBACK_MENU = [
    { id: 'fb-about', label: 'About the UPCI NZ', url: '/about/upci', children: [] },
    { id: 'fb-departments', label: 'Departments', url: '/departments', children: [] },
    { id: 'fb-find', label: 'Find a Church', url: '/find-church', children: [] },
    { id: 'fb-regions', label: 'Regions', url: '/regions', children: [] },
    { id: 'fb-events', label: 'Calendar of Events', url: '/events', children: [] },
    { id: 'fb-contact', label: 'Connect with Us', url: '/connect-with-us', children: [] },
]

export default defineComponent({
    name: 'Navbar',
    components: { MenuLink },
    setup() {
        const { settings } = useSiteSettings()
        // CMS value when set, bundled asset otherwise
        const headerLogo = computed(() => settings.value?.header_logo_url || upciLogo)

        const mobileMenuOpen = ref(false)
        const menuItems = ref([])
        const loading = ref(true)

        const hasChildren = (item) => Array.isArray(item.children) && item.children.length > 0

        // The back-office entry, wherever the CMS menu puts it.
        const isLogin = (item) => /^\/admin(\/|$)/.test(item.url || '')

        const toggleMobileMenu = () => {
            mobileMenuOpen.value = !mobileMenuOpen.value
        }

        const fetchMenuItems = async () => {
            try {
                const response = await axios.get('/api/menu/header')
                menuItems.value = response.data.data || response.data
            } catch (error) {
                console.error('Failed to fetch menu items:', error)
                // The comment here used to promise a default menu and then set
                // an empty array, so a menu-endpoint failure left the site with
                // NO navigation at all — every page reachable only by typing a
                // URL. The fallback below is deliberately short: the handful of
                // destinations someone would actually need if the CMS is down.
                menuItems.value = FALLBACK_MENU
            } finally {
                loading.value = false
            }
        }

        onMounted(() => {
            fetchMenuItems()
        })

        return {
            isPlainAnchor,
            hasChildren,
            isLogin,
            mobileMenuOpen,
            menuItems,
            loading,
            toggleMobileMenu,
            headerLogo
        }
    }
})
</script>

<style scoped>
/* Logo container styling */
.logo-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}
</style>
