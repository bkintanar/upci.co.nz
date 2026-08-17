<template>
    <!-- Delegates to the shared grid rather than adding a third gallery
         implementation (requirement 2). -->
    <GalleryGrid
        :department="data.owner_type === 'department' ? data.department : null"
        :region="data.owner_type === 'region' ? data.region : null"
        :general="data.owner_type === 'general'"
        :heading="data.heading || null"
        :empty-message="data.empty_message"
        section-class="py-16 bg-white"
    />
</template>

<script>
import { defineComponent } from 'vue'
import GalleryGrid from '../GalleryGrid.vue'

export default defineComponent({
    name: 'GalleryBlock',
    components: { GalleryGrid },
    props: { data: { type: Object, required: true } },
})
</script>
