<template>
    <!-- The contact form block (moved from views/ConnectWithUs.vue on
         2026-10-17; owner choice "Both, via a form block"). It sits on
         /connect-with-us and the Apostolic Bible College's Connect page, and
         tells the inbox which page it was sent from. The page's own heading
         now comes from the CMS page (its hero, or the page header). -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md border border-slate-200 p-6 sm:p-8">
                <h2 class="text-2xl font-bold text-slate-900" :class="data.intro ? 'mb-3' : 'mb-6'">{{ data.heading || 'Get in touch' }}</h2>
                <p v-if="data.intro" class="text-slate-600 mb-6">{{ data.intro }}</p>

                <!-- In the page from the start, so a screen reader is already
                     listening when the thank-you arrives; focus moves to it too. -->
                <div role="status" aria-atomic="true">
                    <div
                        v-if="submitted"
                        ref="thanks"
                        tabindex="-1"
                        class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 focus:outline-none"
                    >
                        {{ data.success_message || 'Thank you. Your message has been sent.' }}
                    </div>
                </div>

                <form v-if="!submitted" @submit.prevent="submit" class="space-y-5">
                    <ErrorSummary :errors="errors" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">First Name</label>
                            <input
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                required
                                :class="[
                                    'w-full px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent',
                                    errors.first_name ? 'border-2 border-brand-error focus:ring-brand-error' : 'border border-slate-300 focus:ring-brand-green-700'
                                ]"
                            />
                            <p v-if="errors.first_name" class="mt-1 text-sm text-brand-error font-medium">{{ errors.first_name[0] }}</p>
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1">Last Name</label>
                            <input
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                required
                                :class="[
                                    'w-full px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent',
                                    errors.last_name ? 'border-2 border-brand-error focus:ring-brand-error' : 'border border-slate-300 focus:ring-brand-green-700'
                                ]"
                            />
                            <p v-if="errors.last_name" class="mt-1 text-sm text-brand-error font-medium">{{ errors.last_name[0] }}</p>
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            :class="[
                                    'w-full px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent',
                                    errors.email ? 'border-2 border-brand-error focus:ring-brand-error' : 'border border-slate-300 focus:ring-brand-green-700'
                                ]"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-brand-error font-medium">{{ errors.email[0] }}</p>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700 mb-1">Message</label>
                        <textarea
                            id="message"
                            v-model="form.message"
                            rows="6"
                            required
                            :class="[
                                    'w-full px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent',
                                    errors.message ? 'border-2 border-brand-error focus:ring-brand-error' : 'border border-slate-300 focus:ring-brand-green-700'
                                ]"
                        ></textarea>
                        <p v-if="errors.message" class="mt-1 text-sm text-brand-error font-medium">{{ errors.message[0] }}</p>
                    </div>

                    <div v-if="generalError" class="p-3 rounded-lg border-l-4 border-brand-error bg-white text-brand-ink text-sm">
                        {{ generalError }}
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="sending"
                            class="inline-flex items-center justify-center px-6 py-3 bg-brand-green-700 text-white font-semibold rounded-lg hover:bg-brand-green-900 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            {{ sending ? 'Sending...' : (data.button_text || 'Send') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>

<script>
import { defineComponent, nextTick, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import ErrorSummary from '../layout/ErrorSummary.vue'

export default defineComponent({
    name: 'ContactFormBlock',
    components: { ErrorSummary },
    props: {
        // The block's fields: heading, intro, button_text, success_message.
        data: { type: Object, default: () => ({}) },
    },
    setup() {
        // No setPageMeta here: the CMS page this block sits on owns the
        // title and description.
        const route = useRoute()
        const thanks = ref(null)

        const form = reactive({
            first_name: '',
            last_name: '',
            email: '',
            message: '',
        })
        const errors = ref({})
        const generalError = ref('')
        const sending = ref(false)
        const submitted = ref(false)

        const submit = async () => {
            errors.value = {}
            generalError.value = ''
            sending.value = true

            try {
                const response = await fetch('/api/contact', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    // The page it was sent from, shown as "Sent from" in the
                    // admin's Contact Messages.
                    body: JSON.stringify({ ...form, page: route.path }),
                })

                if (response.ok) {
                    submitted.value = true
                    await nextTick()
                    thanks.value?.focus()
                    return
                }

                if (response.status === 422) {
                    const body = await response.json()
                    errors.value = body.errors || {}
                    return
                }

                generalError.value = 'Something went wrong. Please try again.'
            } catch (e) {
                generalError.value = 'Network error. Please check your connection and try again.'
            } finally {
                sending.value = false
            }
        }

        return { form, errors, generalError, sending, submitted, submit, thanks }
    },
})
</script>
