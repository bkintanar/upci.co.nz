<template>
    <!-- Decorative: the link text says where it goes. -->
    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
        <path v-if="kind === 'chevron'" d="M5 2l6 6-6 6" />
        <path v-else d="M2 8h11M9 4l4 4-4 4" />
    </svg>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'ArrowIcon',
    props: {
        kind: { type: String, default: 'arrow' },
    },
})
</script>
