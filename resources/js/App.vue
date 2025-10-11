<template>
    <div id="app" class="min-h-screen flex flex-col">
        <Navbar />
        <main class="flex-grow">
            <router-view />
        </main>
        <Footer />
    </div>
</template>

<script>
import { defineComponent } from 'vue'
import Navbar from './components/Navbar.vue'
import Footer from './components/Footer.vue'

export default defineComponent({
    name: 'App',
    components: {
        Navbar,
        Footer
    }
})
</script>
