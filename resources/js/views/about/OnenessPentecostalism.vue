<template>
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Oneness Pentecostalism</h1>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                    Understanding the foundational beliefs that unite us as Oneness Pentecostals.
                </p>
            </div>

            <div class="prose prose-lg max-w-none">
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">What is Oneness Pentecostalism?</h2>
                    <p class="text-gray-600 mb-4">
                        Oneness Pentecostalism is a movement within Pentecostal Christianity that emphasizes the oneness of God,
                        rejecting the traditional Trinitarian doctrine in favor of a modalistic view of the Godhead.
                        We believe that God is one person who has manifested Himself in different modes or roles throughout history.
                    </p>
                    <p class="text-gray-600">
                        This belief is rooted in our understanding of Scripture and the early church's teachings about the nature of God.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Key Beliefs</h2>
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">One God</h3>
                                <p class="text-gray-600">We believe in one God who has revealed Himself as Father, Son, and Holy Spirit in different manifestations.</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Jesus Name Baptism</h3>
                                <p class="text-gray-600">We baptize in the name of Jesus Christ, following the apostolic pattern found in the New Testament.</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Holy Ghost Baptism</h3>
                                <p class="text-gray-600">We believe in the necessity of receiving the Holy Ghost with the evidence of speaking in tongues.</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Biblical Foundation</h2>
                        <div class="space-y-4">
                            <div class="border-l-4 border-blue-500 pl-4">
                                <p class="text-gray-600 italic">"Hear, O Israel: The LORD our God, the LORD is one."</p>
                                <p class="text-sm text-gray-500">Deuteronomy 6:4</p>
                            </div>
                            <div class="border-l-4 border-blue-500 pl-4">
                                <p class="text-gray-600 italic">"For there are three that bear record in heaven, the Father, the Word, and the Holy Ghost: and these three are one."</p>
                                <p class="text-sm text-gray-500">1 John 5:7 (KJV)</p>
                            </div>
                            <div class="border-l-4 border-blue-500 pl-4">
                                <p class="text-gray-600 italic">"And Jesus came and spake unto them, saying, All power is given unto me in heaven and in earth. Go ye therefore, and teach all nations, baptizing them in the name of the Father, and of the Son, and of the Holy Ghost."</p>
                                <p class="text-sm text-gray-500">Matthew 28:18-19</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 p-8 rounded-lg mb-12">
                    <h2 class="text-2xl font-bold text-blue-900 mb-4">The Oneness Message</h2>
                    <p class="text-blue-800 mb-4">
                        The Oneness message is not just a theological position—it's a call to return to the apostolic faith
                        and the simplicity of the gospel as preached by the early church. We believe this message brings
                        clarity to understanding who God is and how we should worship Him.
                    </p>
                    <p class="text-blue-800">
                        This understanding shapes every aspect of our worship, ministry, and daily walk with God.
                    </p>
                </div>

                <div class="text-center">
                    <router-link to="/about/beliefs" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Read Our Complete Beliefs
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'OnenessPentecostalism'
})
</script>
