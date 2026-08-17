<template>
    <div>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-br from-brand-green-700 via-brand-green-900 to-brand-ink text-white overflow-hidden">
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
                        <span class="text-brand-green-100">Across New Zealand</span>
                    </h1>

                    <p class="text-xl text-brand-green-100 max-w-3xl mx-auto leading-relaxed mb-8">
                        Connect with your local UPCI community. Find churches, service times, and contact information across New Zealand.
                    </p>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-2xl mx-auto">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white mb-1">{{ churches.length }}</div>
                            <div class="text-brand-green-100 text-sm">Churches</div>
                        </div>
                        <div class="text-center">
                            <!-- Was a hard-coded 6. There are three organisational regions,
                                 and hard-coding the count is how it came to be wrong. -->
                            <div class="text-3xl font-bold text-white mb-1">{{ regions.length ? regions.length - 1 : '—' }}</div>
                            <div class="text-brand-green-100 text-sm">Regions</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white mb-1">24/7</div>
                            <div class="text-brand-green-100 text-sm">Directory</div>
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
                                class="w-full pl-12 pr-4 py-4 text-lg border-0 rounded-2xl focus:ring-4 focus:ring-brand-green-700/20 focus:outline-none transition-all duration-300 shadow-lg bg-white"
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
                                <svg class="w-5 h-5 mr-3 text-brand-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            class="mr-3 text-brand-green-700 focus:ring-brand-green-700"
                                        >
                                        <span class="text-sm text-slate-700 group-hover:text-slate-900">{{ region.label }}</span>
                                    </label>
                                </div>
                                <button v-if="selectedRegion" @click="selectedRegion = ''"
                                        class="mt-3 text-xs text-brand-green-700 hover:text-brand-green-900 font-medium flex items-center">
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
                                            class="mr-3 text-brand-green-700 focus:ring-brand-green-700"
                                        >
                                        <span class="text-sm text-slate-700 group-hover:text-slate-900">{{ day.label }}</span>
                                    </label>
                                </div>
                                <button v-if="selectedServiceDays.length > 0" @click="selectedServiceDays = []"
                                        class="mt-3 text-xs text-brand-green-700 hover:text-brand-green-900 font-medium flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Clear days
                                </button>
                            </div>

                            <!-- Clear All Filters -->
                            <button v-if="searchQuery || selectedRegion || selectedServiceDays.length > 0"
                                    @click="clearFilters"
                                    class="w-full bg-gradient-to-r from-brand-green-700 to-brand-green-900 text-white py-3 px-4 rounded-xl font-semibold hover:from-brand-green-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
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
                                    <svg class="w-5 h-5 mr-2 text-brand-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                    </svg>
                                    Interactive Map
                                </h3>
                                <div class="flex items-center text-sm text-slate-600 bg-slate-100 px-3 py-2 rounded-full">
                                    <div class="w-3 h-3 bg-brand-green-700 rounded-full mr-2"></div>
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
                                            <svg class="w-10 h-10 text-brand-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-semibold text-slate-700 mb-3">Loading churches...</h3>
                                        <p class="text-slate-500">Please wait while we fetch the latest church information</p>
                                    </div>

                                    <div v-else-if="filteredChurches.length > 0">
                                        <!-- Requirement 5: grouped by region, from the data model. -->
                                        <div v-for="group in groupedChurches" :key="group.slug" class="mb-10 last:mb-0">
                                            <div class="flex items-baseline justify-between mb-4 pb-2 border-b border-slate-200">
                                                <h3 class="text-lg font-bold text-slate-900">{{ group.name }}</h3>
                                                <span class="text-sm text-slate-500">{{ group.churches.length }} {{ group.churches.length === 1 ? 'church' : 'churches' }}</span>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div
                                    v-for="church in group.churches"
                                    :key="church.id"
                                    @click="selectChurch(church)"
                                    class="group bg-gradient-to-br from-white to-slate-50 rounded-xl border border-slate-200 p-6 cursor-pointer hover:border-brand-green-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                                    :class="{ 'border-brand-green-700 bg-gradient-to-br from-brand-green-100 to-brand-paper shadow-xl': selectedChurch?.id === church.id }"
                                >
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-slate-900 mb-2 group-hover:text-brand-green-700 transition-colors text-lg">{{ church.name }}</h4>
                                            <div class="space-y-1">
                                                <p class="text-sm text-slate-600">{{ church.address }}</p>
                                                <p class="text-sm text-slate-500">{{ church.city }}, {{ church.region }}</p>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-brand-green-900 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div v-if="formatDistance(church.distance)" class="flex items-center text-sm text-brand-green-700 bg-brand-green-100 px-3 py-2 rounded-full">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            {{ formatDistance(church.distance) }}
                                        </div>
                                        <div v-else-if="!isMappable(church)" class="flex items-center text-sm text-slate-500 bg-slate-100 px-3 py-2 rounded-full" title="This church has no map location on file yet">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Not on the map yet
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
                                <button @click="clearFilters" class="bg-gradient-to-r from-brand-green-700 to-brand-green-900 text-white px-6 py-3 rounded-xl font-semibold hover:from-brand-green-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                                    Clear All Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Order Summary Style Modal -->
        <!-- Requirement 5 / T35: the hand-rolled overlay this replaced had
             role="dialog" but no focus trap and no Escape handling — the page
             behind it stayed tabbable. Modal.vue uses a native <dialog> so the
             browser supplies inertness and Escape, and adds the trap. -->
        <Modal v-model="modalOpen" panel-class="modal-panel--wide"
               :label="selectedChurch ? selectedChurch.name : 'Church details'">
                <div v-if="selectedChurch" class="flex">
                        <!-- Left side - Church Details -->
                        <div class="w-full md:w-1/2 flex-shrink-0">
                            <!-- Header -->
                            <div class="px-6 py-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-bold text-gray-900" id="modal-title">
                                    {{ selectedChurch.name }}
                                </h2>
                                <!-- Must close the DIALOG, not just clear the
                                     selection. Setting selectedChurch = null
                                     alone emptied the panel while the native
                                     <dialog> stayed open, leaving the backdrop
                                     on screen over a blank page. Left over from
                                     the pre-Modal markup. -->
                                <button type="button" @click="modalOpen = false"
                                        class="text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="formatDistance(selectedChurch.distance)" class="text-sm text-gray-500 mt-1">{{ formatDistance(selectedChurch.distance) }}</div>
                            <div v-else-if="!isMappable(selectedChurch)" class="text-sm text-slate-500 mt-1">No map location on file</div>
                        </div>

                        <!-- Content -->
                        <div class="px-6 pb-6">

                            <!-- Details List -->
                            <div class="space-y-3 mb-6">
                                <!-- Address -->
                                <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">{{ selectedChurch.full_address }}</div>
                                        <div class="text-sm text-gray-500">{{ selectedChurch.city }}, {{ selectedChurch.region }}</div>
                                    </div>
                                </div>

                                <!-- Pastor -->
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-900">Pastor</span>
                                    <span class="text-sm font-medium text-gray-900">{{ selectedChurch.pastor }}</span>
                                </div>

                                <!-- Phone -->
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-900">Phone</span>
                                    <a :href="`tel:${selectedChurch.phone}`" class="text-sm font-medium text-gray-900 hover:text-gray-600">
                                        {{ selectedChurch.phone }}
                                    </a>
                                </div>

                                <!-- Services -->
                                <div class="py-2">
                                    <div class="text-sm text-gray-900 mb-2">Service Times</div>
                                    <div class="space-y-2">
                                        <div v-for="service in selectedChurch.services" :key="`${service.service_type}-${service.time}`"
                                             class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600">{{ service.service_type }}</span>
                                            <div class="text-right">
                                                <div class="font-medium text-gray-900">{{ service.time }}</div>
                                                <div class="text-xs text-gray-500">
                                                    <span v-for="(day, index) in service.days_array" :key="day">
                                                        {{ formatDayName(day).substring(0, 3) }}<span v-if="index < service.days_array.length - 1">, </span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="flex flex-col gap-3">
                                <a :href="`tel:${selectedChurch.phone}`"
                                   class="w-full inline-flex items-center justify-center px-4 py-3 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 transition-colors">
                                    Call Church
                                </a>
                                <a :href="`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(selectedChurch.address + ', ' + selectedChurch.city + ', New Zealand')}`"
                                   target="_blank"
                                   class="w-full inline-flex items-center justify-center px-4 py-3 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">
                                    Get Directions
                                </a>
                            </div>
                        </div>
                        </div>

                        <!-- Right side - Map -->
                        <div class="w-full md:w-1/2 flex-shrink-0">
                            <div ref="modalMapContainer" class="w-full h-full min-h-[500px]"></div>
                        </div>
                </div>
        </Modal>

        <!-- Call to Action -->
        <section class="py-16 bg-brand-green-700 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">Can't Find a Church Near You?</h2>
                <p class="text-xl text-brand-green-100 mb-8 max-w-3xl mx-auto">
                    We're always looking to expand our ministry. Contact us to learn about starting a new UPCI church in your area.
                </p>
                <button class="bg-white text-brand-green-700 px-8 py-3 rounded-lg font-semibold hover:bg-brand-green-100 transition-colors">
                    Contact Us About Starting a Church
                </button>
            </div>
        </section>
    </div>
</template>

<script>
// ChurchLocator.vue - Updated: 2025-10-12 00:45:00
import Modal from '../components/Modal.vue'
import { usePageMeta } from '../composables/usePageMeta'
import { defineComponent, ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
// Marker images are bundled by Vite rather than fetched from cdnjs. The CDN
// copy was pinned to 1.7.1 while the installed package is 1.9.4, and it made
// the locator depend on a third party at runtime.
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

// Leaflet resolves its default icon paths relative to the bundle, which breaks
// under Vite — point it at the imported asset URLs instead.
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
})

        export default defineComponent({
            name: 'ChurchLocator',
    components: { Modal },
            setup() {
                const route = useRoute()
                const router = useRouter()

                // A repeated query param arrives as an array and a single one as
                // a string, so normalise before handing it to a ref that is typed
                // as an array. filter(Boolean) drops a valueless `?service_day=`.
                const queryList = (value) => [].concat(value ?? []).filter(Boolean)

                // Seeded from the URL, not empty. The homepage finder hands its
                // term over as `?search=`, and this is the half of that contract
                // that was missing: the block has always pushed the param and
                // this component never read it, so the term was silently dropped
                // and the visitor got every church back with an empty box.
                //
                // Seeded here in setup() rather than in onMounted deliberately.
                // Assigning a ref's INITIAL value does not fire the watcher
                // below, so the single fetchChurches() in onMounted picks these
                // up and there is no duplicate request.
                const searchQuery = ref(typeof route.query.search === 'string' ? route.query.search : '')
                const selectedRegion = ref(typeof route.query.region === 'string' ? route.query.region : '')
                const selectedServiceDays = ref(queryList(route.query.service_day))
                const { setPageMeta } = usePageMeta()
                setPageMeta('Find a Church', 'Find your nearest UPCI church in New Zealand, by region or by name.')
                const selectedChurch = ref(null)
                const modalOpen = ref(false)
                const mapContainer = ref(null)
                const modalMapContainer = ref(null)
                const churches = ref([])
                const regions = ref([])
                const serviceDays = ref([])
                const loading = ref(false)
                const userLocation = ref(null)
                let map = null
                let modalMap = null
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
                        // null, not 0 — an unmappable church has no distance, and
                        // 0 would render as "0m away" and sort to the top
                        distance: isMappable(church)
                            ? calculateDistance(
                                userLocation.value.lat,
                                userLocation.value.lng,
                                church.lat,
                                church.lng
                            )
                            : null
                    }))
                }

                // A church is mappable only if the API gave us usable coordinates.
                const isMappable = (church) =>
                    church && church.has_coordinates && Number.isFinite(church.lat) && Number.isFinite(church.lng)

                // Format distance for display. Returns null when there is nothing
                // meaningful to show, so the template can omit the chip entirely
                // rather than printing "NaNkm away".
                const formatDistance = (distance) => {
                    if (distance === null || distance === undefined || !Number.isFinite(distance)) {
                        return null
                    }
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
                        // organizational_region, not region: the latter filters the
                        // free-text geographic column and would never match a slug.
                        if (selectedRegion.value) params.append('organizational_region', selectedRegion.value)
                        if (selectedServiceDays.value.length > 0) {
                            selectedServiceDays.value.forEach(day => {
                                params.append('service_day', day)
                            })
                        }

                        const response = await fetch(`/api/churches?${params}&_t=${Date.now()}&_cb=${Math.random()}`)
                        const data = await response.json()

                        if (data.success) {
                            churches.value = data.data.map(church => ({
                                ...church,
                                lat: parseFloat(church.latitude),
                                lng: parseFloat(church.longitude),
                                pastor: church.pastor_name,
                                services: church.service_times || [],
                                distance: null // set once we have the user's location, and only if mappable
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
                        // Requirement 5: region comes from the data model, not
                        // from display text. This used to read /api/churches-regions,
                        // the free-text `churches.region` column, which offered
                        // eight values including Rangiora and Rolleston — towns,
                        // not regions — and had no relationship to the Northern /
                        // Central / Southern structure the organisation uses.
                        const response = await fetch(`/api/churches-organizational-regions?_t=${Date.now()}`)
                        const data = await response.json()

                        if (data.success) {
                            regions.value = [
                                { value: '', label: 'All Regions' },
                                ...data.data.map(region => ({
                                    value: region.slug,
                                    label: region.name
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
                        const response = await fetch(`/api/churches-service-days?_t=${Date.now()}`)
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

                // Requirement 5: churches grouped by region. Ordered by the
                // region list from the API (which carries sort_order) rather
                // than alphabetically, so the grouping matches the filter above
                // it and the organisation's own ordering.
                const groupedChurches = computed(() => {
                    const order = regions.value
                        .filter(r => r.value)
                        .map(r => r.value)

                    const groups = new Map()

                    filteredChurches.value.forEach(church => {
                        const slug = church.organizational_region || 'unassigned'
                        if (!groups.has(slug)) {
                            groups.set(slug, {
                                slug,
                                // A church with no region still has to appear —
                                // dropping it here would hide it from the page
                                // entirely, which is the defect fixed in d1e0b0c.
                                name: church.organizational_region_name || 'Not yet assigned to a region',
                                churches: []
                            })
                        }
                        groups.get(slug).churches.push(church)
                    })

                    return [...groups.values()].sort((a, b) => {
                        const ai = order.indexOf(a.slug)
                        const bi = order.indexOf(b.slug)
                        // Unknown/unassigned sorts last rather than first.
                        return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi)
                    })
                })

                // Closing the dialog clears the selection. Without this,
                // reopening the SAME church does nothing: selectedChurch never
                // changed, so no watcher fires.
                watch(modalOpen, (open) => {
                    if (!open) selectedChurch.value = null
                })

                const clearFilters = () => {
                    searchQuery.value = ''
                    selectedRegion.value = ''
                    selectedServiceDays.value = []
                    fetchChurches()
                }

                const selectChurch = (church) => {
                    selectedChurch.value = church
                    modalOpen.value = true
                    // isMappable, not just `map`: five churches have no
                    // coordinates, and setView([null, null]) throws inside
                    // Leaflet and takes the click handler down with it.
                    if (map && isMappable(church)) {
                        // Zoom to a level that shows the area around the church without the popup covering everything
                        map.setView([church.lat, church.lng], 12)
                    }
                    // Two ticks, not one. Modal.vue opens the dialog inside its
                    // own watcher and teleports the panel to <body>, so after a
                    // single tick modalMapContainer is still null and the map
                    // silently never initialises. The old inline markup rendered
                    // in the same pass, which is why one tick used to be enough.
                    nextTick(() => {
                        nextTick(() => initializeModalMap(church))
                    })
                }

                const initializeModalMap = (church) => {
                    if (!modalMapContainer.value || !church) return

                    // Clean up existing modal map
                    if (modalMap) {
                        modalMap.remove()
                        modalMap = null
                    }

                    // Wait a bit for the DOM to be ready
                    setTimeout(() => {
                        if (!modalMapContainer.value) return

                        // Initialize modal map centered on the selected church
                        modalMap = L.map(modalMapContainer.value, {
                            center: [church.lat, church.lng],
                            zoom: 15,
                            zoomControl: true,
                            attributionControl: false
                        })

                        // Add CartoDB Positron tiles (clean, modern style)
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                            subdomains: 'abcd',
                            maxZoom: 20
                        }).addTo(modalMap)

                        // Add marker for the selected church
                        L.marker([church.lat, church.lng])
                            .addTo(modalMap)
                            .bindPopup(`
                                <div class="p-2 max-w-xs">
                                    <h3 class="font-bold text-slate-900 mb-1 text-sm">${church.name}</h3>
                                    <p class="text-xs text-slate-600 mb-1">${church.address}</p>
                                    <p class="text-xs text-slate-500">${church.city}, ${church.region}</p>
                                </div>
                            `, {
                                maxWidth: 200,
                                className: 'custom-popup'
                            })
                            .openPopup()
                    }, 100)
                }

                // Watch for filter changes and refetch data
                watch([searchQuery, selectedRegion, selectedServiceDays], () => {
                    fetchChurches()
                }, { deep: true })

                // Mirror the filters back into the URL, so the locator's own
                // state is shareable and survives a reload. That is what routing
                // was chosen for in the first place; until now only the homepage
                // wrote a param and nothing ever read one.
                //
                // `replace`, not `push`: typing in a search box should not stack
                // a history entry per keystroke.
                //
                // The equality check is not an optimisation. Navigating to an
                // identical location is a duplicated navigation in vue-router,
                // which rejects and surfaces as an unhandled rejection in the
                // console — so this skips the call rather than swallowing the
                // result in an empty catch.
                //
                // Deliberately NOT paired with a watcher on route.query writing
                // back into these refs: with this writer in place that is a
                // feedback loop. Nothing navigates from /find-church to
                // /find-church, so setup() re-runs whenever these params change.
                watch([searchQuery, selectedRegion, selectedServiceDays], () => {
                    const query = {}
                    if (searchQuery.value) query.search = searchQuery.value
                    if (selectedRegion.value) query.region = selectedRegion.value
                    if (selectedServiceDays.value.length) query.service_day = [...selectedServiceDays.value]

                    const current = route.query
                    const same = Object.keys(query).length === Object.keys(current).length
                        && Object.keys(query).every((key) => String(query[key]) === String(current[key]))

                    if (! same) {
                        router.replace({ path: '/find-church', query })
                    }
                }, { deep: true })

        const initializeMap = () => {
            if (!mapContainer.value) return

            // Wait a bit for the DOM to be ready
            setTimeout(() => {
                if (!mapContainer.value) return

                // Requirement 5: New Zealand only. These have to be set at
                // construction, before any fitBounds call — applying them
                // afterwards lets the first fit escape the bounds and the map
                // then snaps back, which reads as a glitch.
                //
                // Viscosity 1.0 makes the edge hard rather than elastic, so a
                // drag cannot leave NZ at all. minZoom 5 stops the user zooming
                // out to the whole Pacific.
                map = L.map(mapContainer.value, {
                    center: [-40.9006, 174.8860],
                    zoom: 6,
                    minZoom: 5,
                    maxBounds: L.latLngBounds([-47.35, 166.3], [-34.1, 178.6]),
                    maxBoundsViscosity: 1.0,
                    zoomControl: true,
                    attributionControl: true
                })

                // Add CartoDB Positron tiles (clean, modern style)
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                    subdomains: 'abcd',
                    maxZoom: 20
                }).addTo(map)

                // Add church markers
                updateMarkers()
            }, 100)
        }

        const updateMarkers = () => {
            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker))
            markers = []

            // Add markers for filtered churches. Churches without coordinates
            // still appear in the list and the region filter — they just cannot
            // be plotted, so they are skipped here rather than excluded upstream.
            filteredChurches.value.filter(isMappable).forEach(church => {

                const marker = L.marker([church.lat, church.lng])
                    .addTo(map)
                    .bindPopup(`
                        <div class="p-2 max-w-xs">
                            <h3 class="font-bold text-slate-900 mb-1 text-sm">${church.name}</h3>
                            <p class="text-xs text-slate-600 mb-1">${church.address}</p>
                            <p class="text-xs text-slate-500 mb-2">${church.city}, ${church.region}</p>
                            <button data-church-id="${church.id}"
                                    class="js-more-info bg-brand-green-700 text-white px-2 py-1 rounded text-xs hover:bg-brand-green-900 transition-colors">
                                More info
                            </button>
                        </div>
                    `, {
                        maxWidth: 200,
                        className: 'custom-popup'
                    })

                // Wire the popup button per-popup instead of through a global.
                // Leaflet builds the popup DOM only when it opens, so the
                // listener has to be attached here rather than at bind time.
                marker.on('popupopen', (event) => {
                    const button = event.popup.getElement()?.querySelector('.js-more-info')
                    button?.addEventListener('click', () => selectChurch(church), { once: true })
                })

                markers.push(marker)
            })

            // Fit map to show all markers if there are any
            if (markers.length > 0) {
                const group = new L.featureGroup(markers)
                // maxZoom caps the fit: filtering to a region with one mappable
                // church would otherwise zoom to street level, which loses all
                // sense of where in the country you are. padding keeps pins off
                // the panel edges.
                map.fitBounds(group.getBounds(), { maxZoom: 12, padding: [40, 40] })
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
            if (modalMap) {
                modalMap.remove()
                modalMap = null
            }
            markers = []
        })

        // The popup's button used to call a window.selectChurchFromMap global,
        // which leaked onto window for the life of the tab, survived unmount,
        // and captured this component's scope. It is now wired per-popup in
        // updateMarkers() via Leaflet's popupopen event.

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
            modalMapContainer,
            churches,
            filteredChurches,
            groupedChurches,
            modalOpen,
            regions,
            serviceDays,
            loading,
            selectChurch,
            clearFilters,
            formatDayName,
            formatDistance,
            isMappable
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
.group:hover .group-hover\:bg-brand-green-100 {
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
