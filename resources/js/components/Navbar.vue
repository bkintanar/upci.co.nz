<template>
    <nav class="bg-slate-800 shadow-lg border-b border-slate-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-28">
                        <div class="flex items-center">
                            <router-link to="/" class="flex-shrink-0 flex items-center group">
                                <img :src="upciLogo"
                                     alt="UPCI New Zealand"
                                     class="h-24 w-auto group-hover:scale-105 transition-transform duration-300">
                            </router-link>
                        </div>

                <div class="hidden md:flex items-center space-x-8">
                    <div class="relative group">
                        <button class="text-white hover:text-blue-300 px-4 py-2 text-sm font-semibold flex items-center transition-colors duration-200">
                            About the UPCI NZ
                            <svg class="ml-2 h-4 w-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-2 w-72 bg-white rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-slate-100">
                            <div class="py-2">
                                <router-link to="/about/upci" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <div class="font-semibold">About the UPCI</div>
                                    <div class="text-xs text-slate-500">Our history and mission</div>
                                </router-link>
                                <router-link to="/about/oneness-pentecostalism" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <div class="font-semibold">Oneness Pentecostalism</div>
                                    <div class="text-xs text-slate-500">Our foundational beliefs</div>
                                </router-link>
                                <router-link to="/about/beliefs" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <div class="font-semibold">Our Beliefs</div>
                                    <div class="text-xs text-slate-500">Core doctrines and practices</div>
                                </router-link>
                                <router-link to="/about/leadership" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <div class="font-semibold">Leadership</div>
                                    <div class="text-xs text-slate-500">Meet our leaders</div>
                                </router-link>
                                <router-link to="/about/general-superintendent" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <div class="font-semibold">General Superintendent</div>
                                    <div class="text-xs text-slate-500">Global leadership</div>
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <router-link to="/get-involved" class="text-white hover:text-blue-300 px-4 py-2 text-sm font-semibold transition-colors duration-200">
                        Get Involved
                    </router-link>

                    <router-link to="/find-church" class="text-white hover:text-blue-300 px-4 py-2 text-sm font-semibold transition-colors duration-200">
                        Find a Church
                    </router-link>

                    <router-link to="/get-involved" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                        Join Us
                    </router-link>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="toggleMobileMenu" class="text-white hover:text-blue-300 p-2 rounded-lg hover:bg-slate-700 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

                <!-- Mobile menu -->
                <div v-show="mobileMenuOpen" class="md:hidden bg-slate-700 border-t border-slate-600">
            <div class="px-4 pt-4 pb-6 space-y-2">
                <div class="space-y-1">
                    <div class="text-sm font-semibold text-slate-300 px-3 py-2">About the UPCI NZ</div>
                    <router-link to="/about/upci" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <div class="font-semibold">About the UPCI</div>
                        <div class="text-xs text-slate-500">Our history and mission</div>
                    </router-link>
                    <router-link to="/about/oneness-pentecostalism" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <div class="font-semibold">Oneness Pentecostalism</div>
                        <div class="text-xs text-slate-500">Our foundational beliefs</div>
                    </router-link>
                    <router-link to="/about/beliefs" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <div class="font-semibold">Our Beliefs</div>
                        <div class="text-xs text-slate-500">Core doctrines and practices</div>
                    </router-link>
                    <router-link to="/about/leadership" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <div class="font-semibold">Leadership</div>
                        <div class="text-xs text-slate-500">Meet our leaders</div>
                    </router-link>
                    <router-link to="/about/general-superintendent" class="block px-6 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <div class="font-semibold">General Superintendent</div>
                        <div class="text-xs text-slate-500">Global leadership</div>
                    </router-link>
                </div>
                <div class="pt-4 border-t border-slate-200">
                    <router-link to="/get-involved" class="block px-3 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors font-semibold">
                        Get Involved
                    </router-link>
                    <router-link to="/find-church" class="block px-3 py-3 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors font-semibold">
                        Find a Church
                    </router-link>
                    <router-link to="/get-involved" class="block mt-2 bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold text-center">
                        Join Us
                    </router-link>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
import { defineComponent, ref } from 'vue'
import upciLogo from '../../images/upci-nz-logo.png'

export default defineComponent({
    name: 'Navbar',
    setup() {
        const mobileMenuOpen = ref(false)

        const toggleMobileMenu = () => {
            mobileMenuOpen.value = !mobileMenuOpen.value
        }

        return {
            mobileMenuOpen,
            toggleMobileMenu,
            upciLogo
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
