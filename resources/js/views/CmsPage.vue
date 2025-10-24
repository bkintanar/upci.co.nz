<template>
    <div v-if="loading" class="min-h-screen flex items-center justify-center">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Loading...</p>
        </div>
    </div>

    <div v-else-if="error" class="min-h-screen flex items-center justify-center">
        <div class="text-center">
            <div class="text-6xl mb-4">😞</div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Page Not Found</h1>
            <p class="text-gray-600 mb-6">{{ error }}</p>
            <router-link to="/" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                Go to Home
            </router-link>
        </div>
    </div>

    <div v-else-if="page">
        <!-- Render content blocks -->
        <div v-for="(block, index) in page.content" :key="index">
            <!-- Hero Section -->
            <section v-if="block.type === 'hero'" :class="getHeroClasses(block.data.style)" class="relative text-white overflow-hidden">
                <div v-if="block.data.background_image" class="absolute inset-0">
                    <img :src="getImageUrl(block.data.background_image)" alt="" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
                </div>
                <div v-else class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.1&quot;%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;1&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                </div>
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32 text-center">
                    <h1 class="text-5xl md:text-6xl font-bold mb-8 text-white leading-tight">{{ block.data.heading }}</h1>
                    <p v-if="block.data.subheading" class="text-xl md:text-2xl mb-12 text-slate-200 max-w-4xl mx-auto leading-relaxed">{{ block.data.subheading }}</p>

                    <!-- Buttons -->
                    <div v-if="block.data.button1_text || block.data.button2_text" class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a v-if="block.data.button1_text && block.data.button1_url"
                           :href="block.data.button1_url"
                           class="group bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <span class="flex items-center justify-center">
                                {{ block.data.button1_text }}
                                <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </span>
                        </a>
                        <a v-if="block.data.button2_text && block.data.button2_url"
                           :href="block.data.button2_url"
                           class="group border-2 border-white/30 text-white px-8 py-4 rounded-lg font-semibold hover:bg-white/10 hover:border-white/50 transition-all duration-300 backdrop-blur-sm">
                            <span class="flex items-center justify-center">
                                {{ block.data.button2_text }}
                                <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Scroll Indicator -->
                <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
                    <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
            </section>

            <!-- Text Block -->
            <section v-else-if="block.type === 'text'" :class="getTextBlockClasses(index)">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center" :class="hasStats(block.data.content) ? 'mb-0' : 'mb-16'">
                        <h2 v-if="block.data.heading" class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">{{ block.data.heading }}</h2>
                        <div class="prose prose-xl max-w-4xl mx-auto text-slate-600 leading-relaxed cms-text-content" :class="{ 'stats-content': hasStats(block.data.content) }" v-html="renderMarkdown(block.data.content)"></div>
                    </div>
                </div>
            </section>

            <!-- Image Block -->
            <section v-else-if="block.type === 'image'" class="py-12 bg-gray-50">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <figure>
                        <img :src="getImageUrl(block.data.image)" :alt="block.data.alt || ''" class="w-full rounded-lg shadow-lg" />
                        <figcaption v-if="block.data.caption" class="mt-4 text-center text-gray-600 italic">{{ block.data.caption }}</figcaption>
                    </figure>
                </div>
            </section>

            <!-- Two Column Layout -->
            <section v-else-if="block.type === 'two_column'" class="py-12 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid md:grid-cols-2 gap-8">
                        <div class="prose prose-lg" v-html="renderMarkdown(block.data.left_content)"></div>
                        <div class="prose prose-lg" v-html="renderMarkdown(block.data.right_content)"></div>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section v-else-if="block.type === 'cta'" :class="getCtaClasses(block.data.style)" class="py-16 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-6">{{ block.data.heading }}</h2>
                    <p v-if="block.data.text" class="text-xl mb-8 max-w-3xl mx-auto opacity-90">{{ block.data.text }}</p>
                    <a :href="block.data.button_url" class="inline-block bg-white text-gray-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                        {{ block.data.button_text }}
                    </a>
                </div>
            </section>

            <!-- Card Grid -->
            <section v-else-if="block.type === 'cards'" class="py-16 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 v-if="block.data.heading" class="text-3xl font-bold text-gray-900 text-center mb-12">{{ block.data.heading }}</h2>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-for="(card, cardIndex) in block.data.items" :key="cardIndex" class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-shadow">
                            <img v-if="card.data.icon" :src="getImageUrl(card.data.icon)" :alt="card.data.title" class="w-16 h-16 mb-4 object-contain" />
                            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ card.data.title }}</h3>
                            <p class="text-gray-600 mb-4">{{ card.data.description }}</p>
                            <a v-if="card.data.link_url" :href="card.data.link_url" class="text-blue-600 hover:text-blue-800 font-semibold">
                                {{ card.data.link_text || 'Learn More' }} →
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Embed Code -->
            <section v-else-if="block.type === 'embed'" class="py-12 bg-white">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 v-if="block.data.title" class="text-3xl font-bold text-gray-900 mb-6">{{ block.data.title }}</h2>
                    <div v-html="block.data.code" class="embed-container"></div>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import { marked } from 'marked';
import { defineComponent, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

export default defineComponent({
    name: 'CmsPage',
    setup() {
        const route = useRoute()
        const page = ref(null)
        const loading = ref(true)
        const error = ref(null)

        const fetchPage = async (slug) => {
            loading.value = true
            error.value = null

            try {
                const response = await fetch(`/api/pages/${slug}`)
                const data = await response.json()

                if (!data.success) {
                    throw new Error(data.message || 'Page not found')
                }

                page.value = data.data
            } catch (err) {
                error.value = err.message
                page.value = null
            } finally {
                loading.value = false
            }
        }

        const renderMarkdown = (content) => {
            return marked(content || '')
        }

        const getImageUrl = (path) => {
            if (!path) return ''
            if (path.startsWith('http')) return path
            return `/storage/${path}`
        }

        const getHeroClasses = (style) => {
            const styles = {
                'gradient-blue': 'bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800',
                'gradient-indigo': 'bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800',
                'gradient-purple': 'bg-gradient-to-br from-purple-600 via-purple-700 to-pink-800',
                'solid-blue': 'bg-blue-600',
                'solid-indigo': 'bg-indigo-600',
            }
            return styles[style] || styles['gradient-blue']
        }

        const getCtaClasses = (style) => {
            const styles = {
                'blue': 'bg-blue-600',
                'indigo': 'bg-indigo-600',
                'purple': 'bg-purple-600',
                'gray': 'bg-gray-700',
            }
            return styles[style] || styles['blue']
        }

        const getTextBlockClasses = (index) => {
            // First text block (Our Mission) - white background
            if (index === 1) {
                return 'py-20 bg-white'
            }
            // Stats block (Our Impact) - slate background
            return 'py-20 bg-slate-50'
        }

        const hasStats = (content) => {
            // Check if content has bullet points (stats pattern)
            return content && content.includes('- **')
        }

        onMounted(() => {
            const slug = route.params.slug
            if (slug) {
                fetchPage(slug)
            }
        })

        watch(() => route.params.slug, (newSlug) => {
            if (newSlug) {
                fetchPage(newSlug)
            }
        })

        return {
            page,
            loading,
            error,
            renderMarkdown,
            getImageUrl,
            getHeroClasses,
            getCtaClasses,
            getTextBlockClasses,
            hasStats,
        }
    }
})
</script>

<style scoped>
.prose {
    color: #475569;
}

.cms-text-content.prose {
    text-align: center;
}

.cms-text-content.prose p {
    font-size: 1.25rem;
    line-height: 1.75;
    margin-bottom: 1rem;
}

/* First paragraph in Our Mission should be larger */
.cms-text-content.prose > p:first-child {
    font-size: 1.25rem;
    line-height: 1.75;
    color: #64748b;
}

/* Stats content styling */
.cms-text-content.stats-content > p:first-child {
    font-size: 1.25rem;
    color: #64748b;
    margin-bottom: 3rem;
}

.prose h1 {
    font-size: 2.25rem;
    font-weight: 800;
    margin-bottom: 1rem;
    color: #0f172a;
}

.prose h2 {
    font-size: 1.875rem;
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #0f172a;
}

.prose h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1.5rem;
    color: #0f172a;
}

.prose p {
    margin-bottom: 1rem;
    line-height: 1.75;
}

.prose ul, .prose ol {
    margin-bottom: 1rem;
    padding-left: 0;
    list-style: none;
    text-align: center;
}

.prose li {
    margin-bottom: 1.5rem;
    font-size: 1.125rem;
    line-height: 1.5;
}

.prose strong {
    font-weight: 700;
    font-size: 3rem;
    line-height: 1;
    display: block;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Alternate colors for stats */
.stats-content .prose li:nth-child(2) strong {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stats-content .prose li:nth-child(3) strong {
    background: linear-gradient(135deg, #475569 0%, #334155 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stats-content .prose li:nth-child(4) strong {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.prose a {
    color: #2563eb;
    text-decoration: underline;
}

.prose a:hover {
    color: #1d4ed8;
}

.prose blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #64748b;
}

.prose hr {
    border: 0;
    height: 0;
    margin: 2rem 0;
    opacity: 0;
}

/* Grid layout for stats when using lists */
.cms-text-content.prose ul {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 3rem;
    margin-top: 3rem;
}

@media (min-width: 768px) {
    .cms-text-content.stats-content.prose ul {
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
    }
}

.cms-text-content.prose li {
    padding: 0;
}

.cms-text-content.stats-content.prose li {
    text-align: center;
}

.embed-container {
    position: relative;
    width: 100%;
}

.embed-container iframe {
    max-width: 100%;
}
</style>
