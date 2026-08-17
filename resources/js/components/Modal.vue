<template>
    <Teleport to="body">
        <dialog
            ref="dialogEl"
            class="modal-dialog"
            :aria-label="label"
            @close="onNativeClose"
            @click="onBackdropClick"
        >
            <div ref="panelEl" :class="['modal-panel', panelClass]" @click.stop>
                <button
                    type="button"
                    class="modal-close"
                    aria-label="Close"
                    @click="close"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <slot />
            </div>
        </dialog>
    </Teleport>
</template>

<script>
import { defineComponent, ref, watch, onBeforeUnmount, nextTick } from 'vue'

const FOCUSABLE = [
    'a[href]', 'button:not([disabled])', 'input:not([disabled])',
    'select:not([disabled])', 'textarea:not([disabled])', '[tabindex]:not([tabindex="-1"])',
].join(',')

/**
 * Native <dialog> in the top layer, teleported to <body>.
 *
 * Uses showModal() rather than a hand-rolled overlay so the browser supplies
 * the modal semantics for free: Escape to close, inert background, correct
 * stacking above any z-index, and the ::backdrop pseudo-element. The one thing
 * it does NOT supply is a focus trap, so that is implemented here.
 *
 * Teleported because a <dialog> nested inside a transformed or overflow-hidden
 * ancestor is clipped by it — the modal would render inside the card that
 * opened it.
 */
export default defineComponent({
    name: 'Modal',
    props: {
        modelValue: { type: Boolean, default: false },
        label: { type: String, default: 'Dialog' },
        // Lets a caller widen the panel; the locator needs room for a map
        // beside the details.
        panelClass: { type: String, default: '' },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const dialogEl = ref(null)
        const panelEl = ref(null)
        let lastFocused = null

        const close = () => emit('update:modelValue', false)

        // Fires for Escape as well as close(), so it is the single place that
        // syncs the native state back to the parent's v-model.
        const onNativeClose = () => {
            if (props.modelValue) close()
        }

        // The dialog element fills the viewport, so a click landing on it
        // rather than on the panel is a backdrop click. The panel stops
        // propagation, so this cannot fire from inside.
        const onBackdropClick = (event) => {
            if (event.target === dialogEl.value) close()
        }

        const onKeydown = (event) => {
            if (event.key !== 'Tab' || !panelEl.value) return

            const items = [...panelEl.value.querySelectorAll(FOCUSABLE)]
                .filter(el => el.offsetParent !== null || el === document.activeElement)

            if (items.length === 0) {
                event.preventDefault()
                return
            }

            const first = items[0]
            const last = items[items.length - 1]

            // Wrap in both directions. Without this, Tab walks out of the
            // dialog and into the inert page behind it, where the user can
            // focus things they cannot see.
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault()
                last.focus()
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault()
                first.focus()
            }
        }

        watch(() => props.modelValue, async (open) => {
            const el = dialogEl.value
            if (!el) return

            if (open) {
                lastFocused = document.activeElement
                el.showModal()
                document.addEventListener('keydown', onKeydown)
                await nextTick()
                const target = panelEl.value?.querySelector(FOCUSABLE)
                target?.focus()
            } else {
                document.removeEventListener('keydown', onKeydown)
                if (el.open) el.close()
                // Return focus to whatever opened the dialog, or the user is
                // dropped back at the top of the document.
                lastFocused?.focus?.()
                lastFocused = null
            }
        })

        onBeforeUnmount(() => {
            document.removeEventListener('keydown', onKeydown)
            if (dialogEl.value?.open) dialogEl.value.close()
        })

        return { dialogEl, panelEl, close, onNativeClose, onBackdropClick }
    }
})
</script>

<style>
.modal-dialog {
    padding: 0;
    border: 0;
    background: transparent;
    max-width: 100vw;
    max-height: 100vh;
    width: 100%;
    height: 100%;
}

.modal-dialog::backdrop {
    background: rgb(15 19 15 / 0.6);
}

.modal-panel {
    position: relative;
    background: #fff;
    border-radius: 0.75rem;
    max-width: 42rem;
    width: calc(100% - 2rem);
    max-height: calc(100vh - 4rem);
    overflow-y: auto;
    margin: 2rem auto;
    box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.35);
}

.modal-panel--wide {
    max-width: 56rem;
}

.modal-close {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 9999px;
    background: rgb(255 255 255 / 0.9);
    color: #334155;
}

.modal-close:hover {
    background: #f1f5f9;
}
</style>
