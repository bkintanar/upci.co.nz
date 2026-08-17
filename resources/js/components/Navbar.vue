<template>
    <nav class="bg-slate-800 shadow-lg border-b border-slate-700 sticky top-0 z-50">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20 gap-4">
                        <div class="flex items-center">
                            <router-link to="/" class="flex-shrink-0 flex items-center group">
                                <img :src="headerLogo"
                                     alt="UPCI New Zealand"
                                     class="h-14 w-auto group-hover:scale-105 transition-transform duration-300">
                            </router-link>
                        </div>

                <div v-if="!loading" class="hidden min-[1400px]:flex items-center flex-nowrap">
                    <template v-for="item in menuItems" :key="item.id">
                        <!-- Menu item with dropdown -->
                        <div v-if="item.children && item.children.length > 0" class="relative group">
                            <router-link
                                v-if="item.url && item.url !== '#' && !item.url.startsWith('http')"
                                :to="item.url"
                                class="text-white hover:text-blue-300 px-2 lg:px-3 py-2 text-sm font-semibold flex items-center whitespace-nowrap transition-colors duration-200">
                                {{ item.label }}
                                <svg class="ml-2 h-4 w-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </router-link>
                            <button v-else class="text-white hover:text-blue-300 px-2 lg:px-3 py-2 text-sm font-semibold flex items-center whitespace-nowrap transition-colors duration-200">
                                {{ item.label }}
                                <svg class="ml-2 h-4 w-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="absolute left-0 mt-2 w-72 bg-white rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-slate-100">
                                <div class="py-2">
                                    <template v-for="child in item.children" :key="child.id">
                                        <a
                                            v-if="child.url && (child.url.startsWith('http') || child.url === '#')"
                                            :href="child.url"
                                            :target="child.open_in_new_tab ? '_blank' : '_self'"
                                            class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                            <div class="font-semibold">{{ child.label }}</div>
                                            <div v-if="child.description" class="text-xs text-slate-500">{{ child.description }}</div>
                                        </a>
                                        <router-link
                                            v-else-if="child.url"
                                            :to="child.url"
                                            class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                            <div class="font-semibold">{{ child.label }}</div>
                                            <div v-if="child.description" class="text-xs text-slate-500">{{ child.description }}</div>
                                        </router-link>

                                        <!-- Third level, e.g. SBQ under Youth Ministry.
                                             Indented inside the same panel rather than a
                                             hover flyout: a nested flyout needs the pointer
                                             to cross a gap without dropping the hover, and
                                             has no touch equivalent at all. -->
                                        <router-link
                                            v-for="grandchild in (child.children || [])"
                                            :key="grandchild.id"
                                            :to="grandchild.url"
                                            class="flex items-center pl-12 pr-6 py-2 text-sm text-slate-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                            <span class="w-3 border-t border-slate-300 mr-3" aria-hidden="true"></span>
                                            {{ grandchild.label }}
                                        </router-link>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Regular menu item (no dropdown) -->
                        <a
                            v-else-if="item.url && (item.url.startsWith('http') || item.url === '#')"
                            :href="item.url"
                            :target="item.open_in_new_tab ? '_blank' : '_self'"
                            class="text-white hover:text-blue-300 px-2 lg:px-3 py-2 text-sm font-semibold whitespace-nowrap transition-colors duration-200">
                            {{ item.label }}
                        </a>
                        <router-link
                            v-else-if="item.url"
                            :to="item.url"
                            class="text-white hover:text-blue-300 px-2 lg:px-3 py-2 text-sm font-semibold whitespace-nowrap transition-colors duration-200">
                            {{ item.label }}
                        </router-link>
                    </template>
                </div>

                <!-- Mobile menu button -->
                <div class="min-[1400px]:hidden flex items-center">
                    <button @click="toggleMobileMenu" class="text-white hover:text-blue-300 p-2 rounded-lg hover:bg-slate-700 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

                <!-- Mobile menu -->
                <div v-show="mobileMenuOpen" class="min-[1400px]:hidden bg-slate-700 border-t border-slate-600">
            <div v-if="!loading" class="px-4 pt-4 pb-6 space-y-2">
                <template v-for="item in menuItems" :key="item.id">
                    <!-- Parent with children -->
                    <div v-if="item.children && item.children.length > 0" class="space-y-1">
                        <router-link
                            v-if="item.url && item.url !== '#' && !item.url.startsWith('http')"
                            :to="item.url"
                            @click="mobileMenuOpen = false"
                            class="block px-3 py-2 text-sm font-semibold text-white hover:bg-slate-600 rounded-lg transition-colors">
                            {{ item.label }}
                        </router-link>
                        <div v-else class="text-sm font-semibold text-slate-300 px-3 py-2">{{ item.label }}</div>
                        <template v-for="child in item.children" :key="child.id">
                            <a
                                v-if="child.url && (child.url.startsWith('http') || child.url === '#')"
                                :href="child.url"
                                :target="child.open_in_new_tab ? '_blank' : '_self'"
                                @click="mobileMenuOpen = false"
                                class="block px-6 py-3 text-sm text-white hover:bg-slate-600 rounded-lg transition-colors">
                                <div class="font-semibold">{{ child.label }}</div>
                                <div v-if="child.description" class="text-xs text-slate-400">{{ child.description }}</div>
                            </a>
                            <router-link
                                v-else-if="child.url"
                                :to="child.url"
                                @click="mobileMenuOpen = false"
                                class="block px-6 py-3 text-sm text-white hover:bg-slate-600 rounded-lg transition-colors">
                                <div class="font-semibold">{{ child.label }}</div>
                                <div v-if="child.description" class="text-xs text-slate-400">{{ child.description }}</div>
                            </router-link>

                            <!-- Third level, indented. Mobile needs this as much as
                                 desktop: without it a grandchild is unreachable on a
                                 phone entirely. -->
                            <router-link
                                v-for="grandchild in (child.children || [])"
                                :key="grandchild.id"
                                :to="grandchild.url"
                                @click="mobileMenuOpen = false"
                                class="flex items-center pl-12 pr-6 py-2 text-sm text-slate-200 hover:bg-slate-600 rounded-lg transition-colors">
                                <span class="w-3 border-t border-slate-500 mr-3" aria-hidden="true"></span>
                                {{ grandchild.label }}
                            </router-link>
                        </template>
                    </div>

                    <!-- Regular item (no children) -->
                    <a
                        v-else-if="item.url && (item.url.startsWith('http') || item.url === '#')"
                        :href="item.url"
                        :target="item.open_in_new_tab ? '_blank' : '_self'"
                        @click="mobileMenuOpen = false"
                        class="block px-3 py-3 text-sm text-white hover:bg-slate-600 rounded-lg transition-colors font-semibold">
                        {{ item.label }}
                    </a>
                    <router-link
                        v-else-if="item.url"
                        :to="item.url"
                        @click="mobileMenuOpen = false"
                        class="block px-3 py-3 text-sm text-white hover:bg-slate-600 rounded-lg transition-colors font-semibold">
                        {{ item.label }}
                    </router-link>
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

export default defineComponent({
    name: 'Navbar',
    setup() {
        const { settings } = useSiteSettings()
        // CMS value when set, bundled asset otherwise
        const headerLogo = computed(() => settings.value?.header_logo_url || upciLogo)

        const mobileMenuOpen = ref(false)
        const menuItems = ref([])
        const loading = ref(true)

        const toggleMobileMenu = () => {
            mobileMenuOpen.value = !mobileMenuOpen.value
        }

        const fetchMenuItems = async () => {
            try {
                const response = await axios.get('/api/menu/header')
                menuItems.value = response.data.data || response.data
            } catch (error) {
                console.error('Failed to fetch menu items:', error)
                // Keep default menu if API fails
                menuItems.value = []
            } finally {
                loading.value = false
            }
        }

        onMounted(() => {
            fetchMenuItems()
        })

        return {
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
