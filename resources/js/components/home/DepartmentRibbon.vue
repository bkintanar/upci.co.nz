<template>
    <!-- The six department hues as one bar: the family shown as a brand
         device. Decorative, so hidden from assistive technology. -->
    <div class="grid grid-cols-6 h-1" aria-hidden="true">
        <span v-for="theme in ribbon" :key="theme" :class="departmentSwatchClasses(theme)"></span>
    </div>
</template>

<script>
import { defineComponent } from 'vue'
import { DEPARTMENT_RIBBON, departmentSwatchClasses } from '../../utils/theme'

export default defineComponent({
    name: 'DepartmentRibbon',
    setup() {
        return { ribbon: DEPARTMENT_RIBBON, departmentSwatchClasses }
    },
})
</script>
