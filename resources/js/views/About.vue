<template>
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">About UPCI New Zealand</h1>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                    Discover more about the United Pentecostal Church International and our presence in New Zealand.
                </p>
            </div>

            <div class="prose prose-lg max-w-none">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                    <div class="space-y-6">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h2 class="text-xl font-semibold text-blue-900 mb-3">About the UPCI</h2>
                            <p class="text-blue-800 mb-4">
                                Learn about the history, mission, and global impact of the United Pentecostal Church International.
                            </p>
                            <router-link to="/about/upci" class="text-blue-600 hover:text-blue-800 font-medium">
                                Learn More →
                            </router-link>
                        </div>

                        <div class="bg-green-50 p-6 rounded-lg">
                            <h2 class="text-xl font-semibold text-green-900 mb-3">Oneness Pentecostalism</h2>
                            <p class="text-green-800 mb-4">
                                Understand the foundational beliefs that unite us as Oneness Pentecostals.
                            </p>
                            <router-link to="/about/oneness-pentecostalism" class="text-green-600 hover:text-green-800 font-medium">
                                Learn More →
                            </router-link>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-purple-50 p-6 rounded-lg">
                            <h2 class="text-xl font-semibold text-purple-900 mb-3">Our Beliefs</h2>
                            <p class="text-purple-800 mb-4">
                                Explore the fundamental doctrines that guide our faith and practice.
                            </p>
                            <router-link to="/about/beliefs" class="text-purple-600 hover:text-purple-800 font-medium">
                                Learn More →
                            </router-link>
                        </div>

                        <div class="bg-orange-50 p-6 rounded-lg">
                            <h2 class="text-xl font-semibold text-orange-900 mb-3">Leadership</h2>
                            <p class="text-orange-800 mb-4">
                                Meet the dedicated leaders who guide UPCI New Zealand.
                            </p>
                            <router-link to="/about/leadership" class="text-orange-600 hover:text-orange-800 font-medium">
                                Learn More →
                            </router-link>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-8 rounded-lg mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">General Superintendent</h2>
                    <p class="text-gray-600 mb-6">
                        Meet the spiritual leader who guides the worldwide UPCI organization and learn about their role
                        in providing leadership to our global family.
                    </p>
                    <router-link to="/about/general-superintendent" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Learn About Our General Superintendent
                    </router-link>
                </div>

                <div class="text-center">
                    <router-link to="/get-involved" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Get Involved
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'About'
})
</script>
