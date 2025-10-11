<template>
    <div>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.1&quot;%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;1&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <div class="text-center">
                    <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm text-white text-sm font-medium mb-6">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Find Your Local Church
                    </div>

                    <h1 class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
                        Discover UPCI Churches<br>
                        <span class="text-blue-200">Across New Zealand</span>
                    </h1>

                    <p class="text-xl text-blue-100 max-w-3xl mx-auto leading-relaxed mb-8">
                        Connect with your local UPCI community. Find churches, service times, and contact information across New Zealand.
                    </p>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-2xl mx-auto">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white mb-1">{{ churches.length }}</div>
                            <div class="text-blue-200 text-sm">Churches</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white mb-1">6</div>
                            <div class="text-blue-200 text-sm">Regions</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white mb-1">24/7</div>
                            <div class="text-blue-200 text-sm">Directory</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search and Map Section -->
        <section class="py-16 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Enhanced Search Bar -->
                <div class="mb-8">
                    <div class="max-w-3xl mx-auto">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by city, region, church name, or address..."
                                class="w-full pl-12 pr-4 py-4 text-lg border-0 rounded-2xl focus:ring-4 focus:ring-blue-500/20 focus:outline-none transition-all duration-300 shadow-lg bg-white"
                            >
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                <div class="text-sm text-slate-400 bg-slate-100 px-3 py-1 rounded-full">
                                    {{ filteredChurches.length }} results
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Modern Filter Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 sticky top-24">
                            <h2 class="text-xl font-bold text-slate-900 mb-6 flex items-center">
                                <svg class="w-5 h-5 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                </svg>
                                Filters
                            </h2>

                            <!-- Region Filter -->
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-slate-700 mb-4">Region</label>
                                <div class="space-y-2">
                                    <label v-for="region in regions.slice(1)" :key="region.value"
                                           class="flex items-center p-3 rounded-xl hover:bg-slate-50 cursor-pointer transition-all duration-200 group">
                                        <input
                                            type="radio"
                                            :value="region.value"
                                            v-model="selectedRegion"
                                            class="mr-3 text-blue-600 focus:ring-blue-500"
                                        >
                                        <span class="text-sm text-slate-700 group-hover:text-slate-900">{{ region.label }}</span>
                                    </label>
                                </div>
                                <button v-if="selectedRegion" @click="selectedRegion = ''"
                                        class="mt-3 text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Clear region
                                </button>
                            </div>

                            <!-- Service Day Filter -->
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-slate-700 mb-4">Service Days</label>
                                <div class="space-y-2">
                                    <label v-for="day in serviceDays" :key="day.value"
                                           class="flex items-center p-3 rounded-xl hover:bg-slate-50 cursor-pointer transition-all duration-200 group">
                                        <input
                                            type="checkbox"
                                            :value="day.value"
                                            v-model="selectedServiceDays"
                                            class="mr-3 text-blue-600 focus:ring-blue-500"
                                        >
                                        <span class="text-sm text-slate-700 group-hover:text-slate-900">{{ day.label }}</span>
                                    </label>
                                </div>
                                <button v-if="selectedServiceDays.length > 0" @click="selectedServiceDays = []"
                                        class="mt-3 text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Clear days
                                </button>
                            </div>

                            <!-- Clear All Filters -->
                            <button v-if="searchQuery || selectedRegion || selectedServiceDays.length > 0"
                                    @click="clearFilters"
                                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-4 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                                Clear All Filters
                            </button>
                        </div>
                    </div>

                    <!-- Main Content Area -->
                    <div class="lg:col-span-3">
                        <!-- Interactive Map -->
                        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 mb-8">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-bold text-slate-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                    </svg>
                                    Interactive Map
                                </h3>
                                <div class="flex items-center text-sm text-slate-600 bg-slate-100 px-3 py-2 rounded-full">
                                    <div class="w-3 h-3 bg-blue-600 rounded-full mr-2"></div>
                                    <span>{{ filteredChurches.length }} UPCI Churches</span>
                                </div>
                            </div>
                            <div ref="mapContainer" class="w-full h-96 lg:h-[500px] rounded-xl map-container overflow-hidden" style="min-height: 300px;"></div>
                        </div>

                        <!-- Modern Church List -->
                        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-bold text-slate-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    Churches
                                </h3>
                                <div class="text-sm text-slate-500 bg-slate-100 px-3 py-2 rounded-full">
                                    {{ filteredChurches.length }} of {{ churches.length }} churches
                                </div>
                            </div>

                                    <div v-if="loading" class="text-center py-16">
                                        <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                                            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-semibold text-slate-700 mb-3">Loading churches...</h3>
                                        <p class="text-slate-500">Please wait while we fetch the latest church information</p>
                                    </div>

                                    <div v-else-if="filteredChurches.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div
                                    v-for="church in filteredChurches"
                                    :key="church.id"
                                    @click="selectChurch(church)"
                                    class="group bg-gradient-to-br from-white to-slate-50 rounded-xl border border-slate-200 p-6 cursor-pointer hover:border-blue-300 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                                    :class="{ 'border-blue-500 bg-gradient-to-br from-blue-50 to-indigo-50 shadow-xl': selectedChurch?.id === church.id }"
                                >
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-slate-900 mb-2 group-hover:text-blue-600 transition-colors text-lg">{{ church.name }}</h4>
                                            <div class="space-y-1">
                                                <p class="text-sm text-slate-600">{{ church.address }}</p>
                                                <p class="text-sm text-slate-500">{{ church.city }}, {{ church.region }}</p>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-sm text-blue-600 bg-blue-50 px-3 py-2 rounded-full">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            {{ formatDistance(church.distance) }}
                                        </div>
                                        <div class="flex items-center text-sm text-emerald-600 bg-emerald-50 px-3 py-2 rounded-full">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ church.services.length }} services
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-16">
                                <div class="w-20 h-20 bg-gradient-to-br from-slate-100 to-slate-200 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-slate-700 mb-3">No churches found</h3>
                                <p class="text-slate-500 mb-6">Try adjusting your search criteria or filters</p>
                                <button @click="clearFilters" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                                    Clear All Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- UX-Optimized Modal -->
        <div v-if="selectedChurch" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop with subtle blur -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="selectedChurch = null"></div>

            <!-- Modal container -->
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all animate-in zoom-in-95 duration-300">
                        <!-- Modern Clean Header -->
                        <div class="px-8 py-8 border-b border-gray-100">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-5">
                                    <div class="flex-shrink-0">
                                        <div class="w-16 h-16 bg-gray-900 rounded-2xl flex items-center justify-center">
                                            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v3M3 12h18M3 6.75h18M3 17.25h18" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h2 class="text-3xl font-bold text-gray-900 mb-3" id="modal-title">
                                            {{ selectedChurch.name }}
                                        </h2>
                                        <div class="flex items-center space-x-4">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-gray-600 bg-gray-100">
                                                {{ formatDistance(selectedChurch.distance) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" @click="selectedChurch = null"
                                        class="rounded-xl p-3 text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-colors">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span class="sr-only">Close modal</span>
                                </button>
                            </div>
                        </div>
                        <!-- Modern Content Section -->
                        <div class="px-8 py-8">
                            <div class="space-y-8">
                                <!-- Address -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Address</h3>
                                    <div class="space-y-1">
                                        <p class="text-gray-900 text-lg">{{ selectedChurch.address }}</p>
                                        <p class="text-gray-600">{{ selectedChurch.city }}, {{ selectedChurch.region }}</p>
                                    </div>
                                </div>

                                <!-- Contact -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Contact</h3>
                                    <div class="space-y-2">
                                        <a :href="`tel:${selectedChurch.phone}`"
                                           class="block text-gray-900 text-lg hover:text-gray-600 transition-colors">
                                            {{ selectedChurch.phone }}
                                        </a>
                                        <a :href="`mailto:${selectedChurch.email}`"
                                           class="block text-gray-600 hover:text-gray-900 transition-colors">
                                            {{ selectedChurch.email }}
                                        </a>
                                    </div>
                                </div>

                                <!-- Pastor -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Pastor</h3>
                                    <p class="text-gray-900 text-lg">{{ selectedChurch.pastor }}</p>
                                </div>

                                <!-- Service Times -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Service Times</h3>
                                    <div class="space-y-3">
                                        <div v-for="service in selectedChurch.services" :key="`${service.service_type}-${service.time}`"
                                             class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                                            <div class="flex-1">
                                                <h4 class="text-gray-900 font-medium">{{ service.service_type }}</h4>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <span class="text-gray-600 font-medium">{{ service.time }}</span>
                                                <div class="flex space-x-2">
                                                    <span v-for="day in service.days_array" :key="day"
                                                          class="px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded">
                                                        {{ formatDayName(day).substring(0, 3) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Footer -->
                        <div class="px-8 py-6 border-t border-gray-100">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a :href="`tel:${selectedChurch.phone}`"
                                   class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-xl text-base font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                    Call Church
                                </a>
                                <a :href="`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(selectedChurch.address + ', ' + selectedChurch.city + ', New Zealand')}`"
                                   target="_blank"
                                   class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gray-900 text-white rounded-xl text-base font-medium hover:bg-gray-800 transition-colors">
                                    Get Directions
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <section class="py-16 bg-blue-600 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">Can't Find a Church Near You?</h2>
                <p class="text-xl text-blue-100 mb-8 max-w-3xl mx-auto">
                    We're always looking to expand our ministry. Contact us to learn about starting a new UPCI church in your area.
                </p>
                <button class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
                    Contact Us About Starting a Church
                </button>
            </div>
        </section>
    </div>
</template>

        <script>
        import { defineComponent, ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
        import L from 'leaflet'
        import 'leaflet/dist/leaflet.css'

        // Fix for default markers in webpack
        delete L.Icon.Default.prototype._getIconUrl
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
            iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        })

        export default defineComponent({
            name: 'ChurchLocator',
            setup() {
                const searchQuery = ref('')
                const selectedRegion = ref('')
                const selectedServiceDays = ref([])
                const selectedChurch = ref(null)
                const mapContainer = ref(null)
                const churches = ref([])
                const regions = ref([])
                const serviceDays = ref([])
                const loading = ref(false)
                const userLocation = ref(null)
                let map = null
                let markers = []

                // Calculate distance between two coordinates using Haversine formula
                const calculateDistance = (lat1, lon1, lat2, lon2) => {
                    const R = 6371 // Earth's radius in kilometers
                    const dLat = (lat2 - lat1) * Math.PI / 180
                    const dLon = (lon2 - lon1) * Math.PI / 180
                    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                            Math.sin(dLon/2) * Math.sin(dLon/2)
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
                    return R * c
                }

                // Get user's current location
                const getUserLocation = () => {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                userLocation.value = {
                                    lat: position.coords.latitude,
                                    lng: position.coords.longitude
                                }
                                // Recalculate distances when location is obtained
                                calculateDistances()
                            },
                            (error) => {
                                console.log('Geolocation error:', error)
                                // Set default location (Wellington, NZ) if geolocation fails
                                userLocation.value = {
                                    lat: -41.2924,
                                    lng: 174.7787
                                }
                                calculateDistances()
                            }
                        )
                    } else {
                        // Set default location if geolocation is not supported
                        userLocation.value = {
                            lat: -41.2924,
                            lng: 174.7787
                        }
                        calculateDistances()
                    }
                }

                // Calculate distances for all churches
                const calculateDistances = () => {
                    if (!userLocation.value) return

                    churches.value = churches.value.map(church => ({
                        ...church,
                        distance: calculateDistance(
                            userLocation.value.lat,
                            userLocation.value.lng,
                            church.lat,
                            church.lng
                        )
                    }))
                }

                // Format distance for display
                const formatDistance = (distance) => {
                    if (distance < 1) {
                        return `${Math.round(distance * 1000)}m away`
                    } else {
                        return `${Math.round(distance * 10) / 10}km away`
                    }
                }

                // Fetch churches from API
                const fetchChurches = async () => {
                    loading.value = true
                    try {
                        const params = new URLSearchParams()
                        if (searchQuery.value) params.append('search', searchQuery.value)
                        if (selectedRegion.value) params.append('region', selectedRegion.value)
                        if (selectedServiceDays.value.length > 0) {
                            selectedServiceDays.value.forEach(day => {
                                params.append('service_day', day)
                            })
                        }

                        const response = await fetch(`/api/churches?${params}`)
                        const data = await response.json()

                        if (data.success) {
                            churches.value = data.data.map(church => ({
                                ...church,
                                lat: parseFloat(church.latitude),
                                lng: parseFloat(church.longitude),
                                pastor: church.pastor_name,
                                services: church.service_times || [],
                                distance: 0 // Will be calculated after user location is obtained
                            }))
                            // Calculate distances if user location is available
                            if (userLocation.value) {
                                calculateDistances()
                            }
                        }
                    } catch (error) {
                        console.error('Error fetching churches:', error)
                    } finally {
                        loading.value = false
                    }
                }

                // Fetch regions from API
                const fetchRegions = async () => {
                    try {
                        const response = await fetch('/api/churches-regions')
                        const data = await response.json()

                        if (data.success) {
                            regions.value = [
                                { value: '', label: 'All Regions' },
                                ...data.data.map(region => ({
                                    value: region.toLowerCase(),
                                    label: region
                                }))
                            ]
                        }
                    } catch (error) {
                        console.error('Error fetching regions:', error)
                    }
                }

                // Fetch service days from API
                const fetchServiceDays = async () => {
                    try {
                        const response = await fetch('/api/churches-service-days')
                        const data = await response.json()

                        if (data.success) {
                            serviceDays.value = data.data.map(day => ({
                                value: day.toLowerCase(),
                                label: day
                            }))
                        }
                    } catch (error) {
                        console.error('Error fetching service days:', error)
                    }
                }

                const filteredChurches = computed(() => {
                    return churches.value
                })

                const clearFilters = () => {
                    searchQuery.value = ''
                    selectedRegion.value = ''
                    selectedServiceDays.value = []
                    fetchChurches()
                }

                const selectChurch = (church) => {
                    selectedChurch.value = church
                    if (map) {
                        // Zoom to a level that shows the area around the church without the popup covering everything
                        map.setView([church.lat, church.lng], 12)
                    }
                }

                // Watch for filter changes and refetch data
                watch([searchQuery, selectedRegion, selectedServiceDays], () => {
                    fetchChurches()
                }, { deep: true })

        const initializeMap = () => {
            if (!mapContainer.value) return

            // Wait a bit for the DOM to be ready
            setTimeout(() => {
                if (!mapContainer.value) return

                // Initialize map centered on New Zealand
                map = L.map(mapContainer.value, {
                    center: [-40.9006, 174.8860],
                    zoom: 6,
                    zoomControl: true,
                    attributionControl: true
                })

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map)

                // Add church markers
                updateMarkers()
            }, 100)
        }

        const updateMarkers = () => {
            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker))
            markers = []

            // Add markers for filtered churches
            filteredChurches.value.forEach(church => {
                const marker = L.marker([church.lat, church.lng])
                    .addTo(map)
                    .bindPopup(`
                        <div class="p-2 max-w-xs">
                            <h3 class="font-bold text-slate-900 mb-1 text-sm">${church.name}</h3>
                            <p class="text-xs text-slate-600 mb-1">${church.address}</p>
                            <p class="text-xs text-slate-500 mb-2">${church.city}, ${church.region}</p>
                            <button onclick="selectChurchFromMap(${church.id})"
                                    class="bg-blue-600 text-white px-2 py-1 rounded text-xs hover:bg-blue-700 transition-colors">
                                View Details
                            </button>
                        </div>
                    `, {
                        maxWidth: 200,
                        className: 'custom-popup'
                    })

                markers.push(marker)
            })

            // Fit map to show all markers if there are any
            if (markers.length > 0) {
                const group = new L.featureGroup(markers)
                map.fitBounds(group.getBounds().pad(0.1))
            }
        }

        // Watch for changes in filtered churches and update markers
        watch(filteredChurches, () => {
            if (map) {
                updateMarkers()
            }
        })

                onMounted(async () => {
                    await nextTick()

                    // Get user location first
                    getUserLocation()

                    // Fetch initial data
                    await Promise.all([
                        fetchChurches(),
                        fetchRegions(),
                        fetchServiceDays()
                    ])

                    // Initialize map after data is loaded
                    initializeMap()
                })

        onUnmounted(() => {
            if (map) {
                map.remove()
                map = null
            }
            markers = []
        })

        // Make selectChurch available globally for popup buttons
        window.selectChurchFromMap = (churchId) => {
            const church = churches.value.find(c => c.id === churchId)
            if (church) {
                selectChurch(church)
            }
        }

        // Format day names for display
        const formatDayName = (day) => {
            const dayMap = {
                'monday': 'Monday',
                'tuesday': 'Tuesday',
                'wednesday': 'Wednesday',
                'thursday': 'Thursday',
                'friday': 'Friday',
                'saturday': 'Saturday',
                'sunday': 'Sunday'
            }
            return dayMap[day.toLowerCase()] || day
        }

        return {
            searchQuery,
            selectedRegion,
            selectedServiceDays,
            selectedChurch,
            mapContainer,
            churches,
            filteredChurches,
            regions,
            serviceDays,
            loading,
            selectChurch,
            clearFilters,
            formatDayName,
            formatDistance
        }
    }
})
</script>

<style scoped>
/* UX-Optimized Modal Styles */
/* Fix Leaflet popup z-index issues */
:deep(.leaflet-popup) {
    z-index: 1000 !important;
}

/* Custom animations for better UX */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Apply animations */
.animate-in {
    animation: fadeIn 0.3s ease-out;
}

.zoom-in-95 {
    animation: zoomIn 0.3s ease-out;
}

/* Enhanced hover effects */
.group:hover .group-hover\:bg-blue-100 {
    background-color: rgb(219 234 254);
}

.group:hover .group-hover\:bg-green-100 {
    background-color: rgb(220 252 231);
}

.group:hover .group-hover\:bg-purple-100 {
    background-color: rgb(243 232 255);
}

.group:hover .group-hover\:bg-orange-100 {
    background-color: rgb(255 237 213);
}

:deep(.leaflet-popup-content-wrapper) {
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    background: white;
    max-width: 200px;
}

:deep(.leaflet-popup-content) {
    margin: 6px 8px;
    line-height: 1.3;
    color: #374151;
    font-size: 12px;
}

/* Custom popup styling for smaller, more compact popups */
:deep(.custom-popup .leaflet-popup-content-wrapper) {
    max-width: 200px;
    padding: 0;
}

:deep(.custom-popup .leaflet-popup-content) {
    margin: 4px 6px;
    line-height: 1.2;
}

:deep(.leaflet-popup-tip) {
    background: white;
    border: 1px solid #ccc;
}

:deep(.leaflet-popup-close-button) {
    color: #6b7280;
    font-size: 18px;
    font-weight: bold;
}

:deep(.leaflet-popup-close-button:hover) {
    color: #374151;
}

/* Ensure map container has proper z-index */
.map-container {
    position: relative;
    z-index: 1;
}

/* Additional popup styling */
:deep(.leaflet-popup-content h3) {
    margin: 0 0 2px 0;
    font-size: 12px;
    font-weight: 600;
    color: #111827;
}

:deep(.leaflet-popup-content p) {
    margin: 0 0 2px 0;
    font-size: 11px;
    color: #6b7280;
}

:deep(.leaflet-popup-content button) {
    margin-top: 4px;
    padding: 2px 6px;
    font-size: 10px;
    border-radius: 3px;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

:deep(.leaflet-popup-content button:hover) {
    background-color: #1d4ed8;
}
</style>
