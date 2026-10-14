<template>
    <!-- The photo viewer (direction D3, "Quiet frame"): the one dark room in a
         paper house. Modal supplies the top-layer <dialog>, Esc, the backdrop
         click, the Tab trap and focus back to whatever opened it; this adds the
         photo, its caption, "Photo 3 of 12", previous and next (arrow keys and
         swipe too) and nothing else. No autoplay. -->
    <Modal
        :model-value="modelValue"
        :label="label ? `${label}: photo viewer` : 'Photo viewer'"
        :show-close="false"
        dialog-class="modal-dialog--photo"
        panel-class="modal-panel--photo"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <div @keydown="onKeydown" @touchstart.passive="onTouchStart" @touchend="onTouchEnd">
            <div class="flex items-center justify-between gap-4 mb-3">
                <p class="min-w-0 truncate text-xs font-bold uppercase tracking-[0.12em] text-white/80">{{ label }}</p>
                <button type="button" :class="buttonClass" @click="close">
                    Close
                    <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 3l10 10M13 3 3 13" />
                    </svg>
                </button>
            </div>

            <!-- Whole and never upscaled: width and height stay auto, so the
                 photo shows at its own size until the viewport caps it. The
                 caps leave room for the two lines of controls. -->
            <img
                v-if="current"
                :key="current.src"
                :src="current.src"
                :alt="current.alt || ''"
                :width="current.width || null"
                :height="current.height || null"
                decoding="async"
                class="block mx-auto w-auto h-auto max-w-[calc(92vw-1.5rem)] max-h-[calc(88vh-8.5rem)] max-sm:max-h-[calc(88vh-11rem)] bg-white/5 touch-pan-y"
            />

            <div class="flex flex-wrap items-center gap-x-6 gap-y-3 mt-3 min-h-11">
                <p v-if="current && current.caption" class="flex-[1_1_14rem] min-w-0 text-base leading-snug text-white">{{ current.caption }}</p>
                <div v-if="counted" class="flex items-center gap-2 ml-auto max-sm:w-full max-sm:justify-between">
                    <button v-if="many" type="button" :class="squareClass" aria-label="Previous photo" @click="step(-1)">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12.5 4 6.5 10l6 6" />
                        </svg>
                    </button>
                    <p class="min-w-[8.5rem] text-center text-[15px] font-medium text-white/90 tabular-nums" role="status" aria-live="polite">
                        Photo {{ index + 1 }} of {{ photos.length }}
                    </p>
                    <button v-if="many" type="button" :class="squareClass" aria-label="Next photo" @click="step(1)">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M7.5 4l6 6-6 6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script>
import { computed, defineComponent, onBeforeUnmount, watch } from 'vue'
import Modal from './Modal.vue'

// Square, uppercase and tracked like the homepage's buttons, outlined in white
// on the ink panel. Literal strings so Tailwind emits them.
const BUTTON = 'inline-flex items-center justify-center gap-2 min-w-11 h-11 px-3.5 shrink-0 border border-white/40 rounded-[2px] '
    + 'text-sm font-bold uppercase tracking-[0.07em] text-white hover:bg-white/10 hover:border-white/70 transition-colors '
    + 'focus:outline-none focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-[3px] focus-visible:outline-white'
const SQUARE = 'inline-flex items-center justify-center w-11 h-11 shrink-0 border border-white/40 rounded-[2px] '
    + 'text-white hover:bg-white/10 hover:border-white/70 transition-colors '
    + 'focus:outline-none focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-[3px] focus-visible:outline-white'

// A swipe is a horizontal move of at least this many pixels that is clearly
// more sideways than up and down, so scrolling a tall photo is never a swipe.
const SWIPE_PX = 50

/**
 * Shows one photo large, with previous and next through a set.
 *
 * photos: [{ src, alt, caption, width?, height? }]. width and height are
 * optional; they only reserve the shape while the image loads.
 * counted: false for a single announcement photo, which has no "1 of 1".
 */
export default defineComponent({
    name: 'PhotoViewer',
    components: { Modal },
    props: {
        modelValue: { type: Boolean, default: false },
        photos: { type: Array, default: () => [] },
        index: { type: Number, default: 0 },
        label: { type: String, default: '' },
        counted: { type: Boolean, default: true },
    },
    emits: ['update:modelValue', 'update:index'],
    setup(props, { emit }) {
        const current = computed(() => props.photos[props.index] || null)
        const many = computed(() => props.photos.length > 1)

        const close = () => emit('update:modelValue', false)

        // Wraps at both ends: after the last photo comes the first.
        const step = (delta) => {
            if (!many.value) return
            const count = props.photos.length
            emit('update:index', (props.index + delta + count) % count)
        }

        const onKeydown = (event) => {
            if (event.key === 'ArrowRight') {
                event.preventDefault()
                step(1)
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault()
                step(-1)
            }
        }

        let touchX = null
        let touchY = null

        const onTouchStart = (event) => {
            if (event.touches.length !== 1) return
            touchX = event.touches[0].clientX
            touchY = event.touches[0].clientY
        }

        const onTouchEnd = (event) => {
            if (touchX === null) return
            const dx = event.changedTouches[0].clientX - touchX
            const dy = event.changedTouches[0].clientY - touchY
            touchX = touchY = null
            if (Math.abs(dx) >= SWIPE_PX && Math.abs(dx) > Math.abs(dy) * 1.2) step(dx < 0 ? 1 : -1)
        }

        // The page behind must not scroll while the viewer is open; a native
        // modal dialog makes it inert but does not stop it scrolling.
        let previousOverflow = ''
        const lockScroll = (locked) => {
            const root = document.documentElement
            if (locked) {
                previousOverflow = root.style.overflow
                root.style.overflow = 'hidden'
            } else {
                root.style.overflow = previousOverflow
            }
        }

        watch(() => props.modelValue, (open) => lockScroll(open))
        onBeforeUnmount(() => {
            if (props.modelValue) lockScroll(false)
        })

        return {
            current,
            many,
            close,
            step,
            onKeydown,
            onTouchStart,
            onTouchEnd,
            buttonClass: BUTTON,
            squareClass: SQUARE,
        }
    },
})
</script>
