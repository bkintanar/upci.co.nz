<template>
    <!-- Text beside a large photo (the homepage's About). Until a photo is
         uploaded the frame holds a designed panel built from real New Zealand
         places, never a grey box or "photo coming soon". -->
    <section :class="[sectionClasses, backgroundClass]" :aria-labelledby="headingId">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div :class="['grid gap-8 lg:gap-16 lg:items-start', imageLeft ? 'lg:grid-cols-[7fr_5fr]' : 'lg:grid-cols-[5fr_7fr]']">
                <div :class="imageLeft ? 'lg:order-2' : ''">
                    <SectionHead :id="headingId" :heading="data.heading" />
                    <div
                        v-if="data.content"
                        class="space-y-4 text-[17px] leading-relaxed text-brand-ink [&_a]:text-brand-green-700 [&_a]:underline"
                        v-html="renderMarkdown(data.content)"
                    ></div>
                    <p v-if="data.tagline" class="mt-4 text-[22px] leading-snug font-bold tracking-[-0.01em] text-brand-green-900">
                        {{ data.tagline }}
                    </p>
                    <MenuLink
                        v-if="data.button_text && data.button_url"
                        :item="{ url: data.button_url }"
                        :class="['mt-7', ctaButton('green')]"
                    >{{ data.button_text }} <ArrowIcon /></MenuLink>
                </div>

                <figure class="relative aspect-square md:aspect-[16/10] border border-brand-grey-200 overflow-hidden bg-brand-green-100">
                    <img
                        v-if="data.image"
                        :src="imageUrl(data.image)"
                        :alt="data.image_alt || ''"
                        loading="lazy"
                        class="absolute inset-0 w-full h-full object-cover"
                    />

                    <div v-else class="absolute inset-0 flex flex-col">
                        <img
                            :src="collage"
                            alt="Five New Zealand places side by side: Auckland's Sky Tower, the Beehive in Wellington, ChristChurch Cathedral, an arched bridge over the Waikato River in Hamilton, and the coast at Tauranga"
                            width="2128" height="788"
                            loading="lazy"
                            class="w-full h-auto aspect-[2128/788] shrink-0"
                        />
                        <!-- Names under each place. The column widths follow the
                             collage's own panels. On a phone the five names run
                             as one line instead. -->
                        <ol class="hidden md:grid grid-cols-[13.2fr_20.2fr_22.1fr_21.6fr_22.9fr] shrink-0 border-b border-[color:oklch(0.34_0.07_143_/_0.22)]" aria-hidden="true">
                            <li
                                v-for="(place, i) in places"
                                :key="place"
                                :class="['px-2 pt-2 pb-[9px] text-[13px] font-bold leading-tight text-brand-green-900', i > 0 ? 'border-l border-[color:oklch(0.34_0.07_143_/_0.28)]' : '']"
                            >{{ place }}</li>
                        </ol>
                        <p class="md:hidden px-4 pt-2.5 text-[13px] font-semibold leading-snug text-brand-green-900" aria-hidden="true">
                            <span class="mr-1 text-xs font-bold tracking-[0.1em] uppercase text-brand-green-700">Pictured</span>
                            {{ places.join(' · ') }}
                        </p>
                        <div class="flex-1 flex flex-col justify-end gap-2 px-4 pt-3 pb-[18px] md:px-6 md:pt-5 md:pb-6">
                            <p class="text-xs font-bold tracking-[0.12em] uppercase text-brand-green-700">Where we gather</p>
                            <p v-if="data.fallback_caption" class="max-w-[30ch] text-[21px] md:text-[26px] leading-tight font-bold tracking-[-0.015em] text-brand-green-900">
                                {{ data.fallback_caption }}
                            </p>
                        </div>
                    </div>
                </figure>
            </div>
        </div>
    </section>
</template>

<script>
import { defineComponent, computed, useId } from 'vue'
import { renderMarkdown } from '../../utils/markdown'
import { ctaButton } from '../../utils/theme'
import MenuLink from '../MenuLink.vue'
import ArrowIcon from '../home/ArrowIcon.vue'
import SectionHead from '../home/SectionHead.vue'
// A pale collage of five real places, bundled rather than uploaded: it is the
// designed state of an empty photo slot, not content.
import collage from '../../../images/home/nz-landmarks-collage.jpg'

const BACKGROUNDS = {
    white: 'bg-white',
    slate: 'bg-slate-50',
    paper: 'bg-brand-paper',
}

export default defineComponent({
    name: 'FeatureBlock',
    components: { MenuLink, ArrowIcon, SectionHead },
    props: {
        data: { type: Object, required: true },
        // When the block above is on the same ground, its bottom padding
        // already makes the gap; doubling it leaves a hole.
        flushTop: { type: Boolean, default: false },
    },
    setup(props) {
        const headingId = useId()

        const imageLeft = computed(() => props.data.image_side === 'left')
        const backgroundClass = computed(() => BACKGROUNDS[props.data.background] || BACKGROUNDS.white)
        const sectionClasses = computed(() => (props.flushTop ? 'pb-14 md:pb-20 xl:pb-24' : 'py-14 md:py-20 xl:py-24'))

        const imageUrl = (path) => (!path ? '' : path.startsWith('http') ? path : `/storage/${path}`)

        return {
            headingId,
            imageLeft,
            backgroundClass,
            sectionClasses,
            imageUrl,
            renderMarkdown,
            ctaButton,
            collage,
            places: ['Auckland', 'Wellington', 'Christchurch', 'Hamilton', 'Tauranga'],
        }
    },
})
</script>
