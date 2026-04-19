<template>
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Departments</h1>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                    Discover the various ministries and opportunities to serve within UPCI New Zealand.
                    There's a place for everyone to grow, serve, and make a difference.
                </p>
            </div>

            <!-- Social links -->
            <div class="flex flex-wrap justify-center gap-4 mb-12">
                <a href="https://www.facebook.com" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-blue-600 hover:text-white transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook
                </a>
                <a href="https://www.instagram.com" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-pink-600 hover:text-white transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    Instagram
                </a>
            </div>

            <!-- General – Gallery -->
            <section id="gallery" class="mb-16">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">General – Gallery</h2>
                <div v-if="galleryLoading" class="text-center py-8 text-gray-500">Loading gallery...</div>
                <div v-else-if="galleryItems.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="item in galleryItems" :key="item.id" class="rounded-lg overflow-hidden border border-gray-200">
                        <img v-if="item.image_url" :src="item.image_url" :alt="item.title || 'Gallery'" class="w-full h-48 object-cover">
                        <div class="p-3">
                            <p v-if="item.title" class="font-medium text-gray-900">{{ item.title }}</p>
                            <p v-if="item.description" class="text-sm text-gray-600 line-clamp-2">{{ item.description }}</p>
                        </div>
                    </div>
                </div>
                <p v-else class="text-gray-500">No gallery items yet.</p>
            </section>

            <div class="prose prose-lg max-w-none">
                <!-- Ministries Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
                    <!-- Children's Ministry -->
                    <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-8 rounded-lg border border-yellow-200">
                        <div class="flex items-center mb-6">
                            <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Children's Ministry</h2>
                                <p class="text-gray-600">Ages 0-12</p>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-4">
                            Our children's ministry provides a safe, fun, and educational environment where kids can learn about Jesus
                            through age-appropriate activities, Bible stories, and interactive lessons.
                        </p>
                        <div class="space-y-2 mb-6">
                            <h3 class="font-semibold text-gray-900">Activities Include:</h3>
                            <ul class="text-gray-600 space-y-1">
                                <li>• Sunday School classes</li>
                                <li>• Vacation Bible School (VBS)</li>
                                <li>• Children's choir</li>
                                <li>• Holiday programs</li>
                                <li>• Family events</li>
                            </ul>
                        </div>
                        <div class="bg-yellow-100 p-4 rounded-lg">
                            <p class="text-yellow-800 text-sm font-medium">
                                "Train up a child in the way he should go: and when he is old, he will not depart from it." - Proverbs 22:6
                            </p>
                        </div>
                    </div>

                    <!-- Youth Ministry -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-8 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Youth Ministry</h2>
                                <p class="text-gray-600">Ages 13-25</p>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-4">
                            Our youth ministry creates a dynamic environment for teenagers and young adults to grow in their faith,
                            build lasting friendships, and discover their purpose in God's plan.
                        </p>
                        <div class="space-y-2 mb-6">
                            <h3 class="font-semibold text-gray-900">Activities Include:</h3>
                            <ul class="text-gray-600 space-y-1">
                                <li>• Weekly youth services</li>
                                <li>• Youth camps and retreats</li>
                                <li>• Mission trips</li>
                                <li>• Sports and recreation</li>
                                <li>• Leadership development</li>
                            </ul>
                        </div>
                        <div class="bg-blue-100 p-4 rounded-lg">
                            <p class="text-blue-800 text-sm font-medium">
                                "Let no man despise thy youth; but be thou an example of the believers..." - 1 Timothy 4:12
                            </p>
                        </div>
                    </div>

                    <!-- Men's Ministry -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-8 rounded-lg border border-green-200">
                        <div class="flex items-center mb-6">
                            <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Men's Ministry</h2>
                                <p class="text-gray-600">All Ages</p>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-4">
                            Our men's ministry focuses on building strong Christian men who lead their families and communities
                            with integrity, wisdom, and spiritual strength.
                        </p>
                        <div class="space-y-2 mb-6">
                            <h3 class="font-semibold text-gray-900">Activities Include:</h3>
                            <ul class="text-gray-600 space-y-1">
                                <li>• Men's breakfast meetings</li>
                                <li>• Bible study groups</li>
                                <li>• Service projects</li>
                                <li>• Men's retreats</li>
                                <li>• Mentorship programs</li>
                            </ul>
                        </div>
                        <div class="bg-green-100 p-4 rounded-lg">
                            <p class="text-green-800 text-sm font-medium">
                                "Watch ye, stand fast in the faith, quit you like men, be strong." - 1 Corinthians 16:13
                            </p>
                        </div>
                    </div>

                    <!-- Ladies Ministry -->
                    <div class="bg-gradient-to-br from-pink-50 to-rose-50 p-8 rounded-lg border border-pink-200">
                        <div class="flex items-center mb-6">
                            <div class="bg-pink-100 w-16 h-16 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-8 h-8 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Ladies Ministry</h2>
                                <p class="text-gray-600">All Ages</p>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-4">
                            Our ladies ministry creates a supportive community where women can grow spiritually,
                            develop meaningful relationships, and serve together in God's kingdom.
                        </p>
                        <div class="space-y-2 mb-6">
                            <h3 class="font-semibold text-gray-900">Activities Include:</h3>
                            <ul class="text-gray-600 space-y-1">
                                <li>• Ladies' fellowship meetings</li>
                                <li>• Bible study groups</li>
                                <li>• Craft and hobby groups</li>
                                <li>• Ladies' conferences</li>
                                <li>• Community outreach</li>
                            </ul>
                        </div>
                        <div class="bg-pink-100 p-4 rounded-lg">
                            <p class="text-pink-800 text-sm font-medium">
                                "She is clothed with strength and dignity; she can laugh at the days to come." - Proverbs 31:25
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Additional Ministries -->
                <div class="mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Additional Opportunities</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gray-50 p-6 rounded-lg text-center">
                            <div class="bg-purple-100 w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Music Ministry</h3>
                            <p class="text-gray-600 text-sm">Join our choir, band, or worship team to lead in praise and worship.</p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-lg text-center">
                            <div class="bg-purple-100 w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Teaching Ministry</h3>
                            <p class="text-gray-600 text-sm">Share your knowledge and help others grow in their understanding of God's Word.</p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-lg text-center">
                            <div class="bg-purple-100 w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Evangelism</h3>
                            <p class="text-gray-600 text-sm">Help spread the gospel through outreach, missions, and community service.</p>
                        </div>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="bg-blue-600 text-white p-8 rounded-lg text-center">
                    <h2 class="text-2xl font-bold mb-4">Ready to Get Started?</h2>
                    <p class="text-blue-100 mb-6">
                        There's a place for everyone in UPCI New Zealand. Whether you're looking to grow spiritually,
                        serve others, or build meaningful relationships, we have opportunities that will fit your interests and schedule.
                    </p>
                    <div class="space-y-4">
                        <p class="text-blue-100">
                            Contact your local UPCI church to learn more about specific ministry opportunities in your area.
                        </p>
                        <div class="bg-blue-700 p-4 rounded-lg">
                            <p class="text-blue-100 text-sm">
                                "For we are his workmanship, created in Christ Jesus for good works, which God hath before ordained
                                that we should walk in them." - Ephesians 2:10
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue'

export default defineComponent({
    name: 'Departments',
    setup() {
        const galleryItems = ref([])
        const galleryLoading = ref(true)

        onMounted(async () => {
            try {
                const res = await fetch('/api/gallery?department=general')
                const data = await res.json()
                if (data.success && data.data) {
                    galleryItems.value = data.data
                }
            } catch (_) {
                galleryItems.value = []
            } finally {
                galleryLoading.value = false
            }
        })

        return { galleryItems, galleryLoading }
    }
})
</script>
