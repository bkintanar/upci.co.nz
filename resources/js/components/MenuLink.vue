<template>
    <!-- External URLs, the '#' placeholder and server paths like /admin
         are plain anchors (see utils/menuUrl.js). Handing an
         absolute URL to <router-link> turns https://example.com into an
         in-app path, which lands on the SPA's not-found route instead of
         leaving the site. -->
    <a
        v-if="isExternal"
        :href="item.url"
        :target="item.open_in_new_tab ? '_blank' : '_self'"
        rel="noopener"
    ><slot>{{ item.label }}</slot></a>
    <router-link v-else-if="item.url" :to="item.url"><slot>{{ item.label }}</slot></router-link>
    <!-- No URL at all: render the label rather than a link to nowhere. -->
    <span v-else><slot>{{ item.label }}</slot></span>
</template>

<script>
import { computed, defineComponent } from 'vue'
import { isPlainAnchor } from '../utils/menuUrl'

/**
 * One menu entry, rendered as the right kind of link for its URL. Classes and
 * listeners (for example the mobile menu's close-on-click) fall through to the
 * rendered element, so callers style it exactly as they did the bare link.
 */
export default defineComponent({
    name: 'MenuLink',
    props: {
        // A menu item from /api/menu/*: { url, label, open_in_new_tab }.
        item: { type: Object, required: true },
    },
    setup(props) {
        const isExternal = computed(() => isPlainAnchor(props.item?.url))

        return { isExternal }
    },
})
</script>
