<template>
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">About the UPCI</h1>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                    Learn about the United Pentecostal Church International and our mission worldwide.
                </p>
            </div>

            <div class="prose prose-lg max-w-none">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-12">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Our History</h2>
                        <p class="text-gray-600 mb-4">
                            The United Pentecostal Church International was formed in 1945 by the merger of the Pentecostal Church, Incorporated,
                            and the Pentecostal Assemblies of Jesus Christ. Since then, UPCI has grown to become one of the largest Oneness Pentecostal
                            organizations in the world.
                        </p>
                        <p class="text-gray-600">
                            With headquarters in Hazelwood, Missouri, UPCI serves millions of members across the globe, including our vibrant
                            New Zealand district.
                        </p>
                    </div>
                    <div class="bg-gray-100 p-8 rounded-lg">
                        <h3 class="text-xl font-semibold mb-4">Key Statistics</h3>
                        <ul class="space-y-2 text-gray-600">
                            <li>• Over 40,000 churches worldwide</li>
                            <li>• More than 6 million members globally</li>
                            <li>• Active in over 200 countries</li>
                            <li>• Strong presence in New Zealand</li>
                        </ul>
                    </div>
                </div>

                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Our Mission</h2>
                    <p class="text-gray-600 mb-4">
                        The UPCI is committed to fulfilling the Great Commission by spreading the gospel of Jesus Christ,
                        establishing churches, training ministers, and providing fellowship and support for our members worldwide.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">Evangelism</h3>
                            <p class="text-blue-800">Spreading the gospel message to all nations and peoples.</p>
                        </div>
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">Discipleship</h3>
                            <p class="text-blue-800">Training and equipping believers for ministry and service.</p>
                        </div>
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">Fellowship</h3>
                            <p class="text-blue-800">Building strong Christian communities and relationships.</p>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <router-link to="/about/oneness-pentecostalism" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Learn About Oneness Pentecostalism
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'AboutUPCI'
})
</script>
