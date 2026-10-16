<template>
    <!-- Form ABC-2, the pastor's reference (owner request, 2026-10-17, from
         Brandon Borders' draft). The second of the college's forms in
         direction D3, "the application as a document" (.claude/design/
         abc-enrolment/direction-approved.md): the same masthead, numbered
         sections, hanging clause numbers and signature block as Form ABC-1,
         so a pastor following the emailed link lands on the college's own
         paper. Reached only through that link; the token is the only key. -->
    <div class="abc">
        <div class="doc">
            <header class="mast">
                <div class="mast__rule"></div>
                <div class="mast__ref">
                    <span>Apostolic Bible College · UPCI New Zealand</span>
                    <span>Form ABC-2 · Pastor's reference</span>
                </div>
                <h1>Pastor's Reference <span class="sub">— Apostolic Bible College</span></h1>
                <dl v-if="state === 'form'" class="mast__meta">
                    <div><dt>Applicant</dt><dd>{{ context.applicant_name }}</dd></div>
                    <div><dt>Time</dt><dd>About 2 minutes</dd></div>
                    <div><dt>Marked <span aria-hidden="true">*</span></dt><dd>Required answer</dd></div>
                </dl>
            </header>

            <p v-if="state === 'loading'" class="intro" role="status" aria-live="polite">Loading the questionnaire…</p>

            <!-- Sent, or sent before: a receipt in the form's own language. -->
            <section v-else-if="state === 'sent' || state === 'already'" class="success">
                <div class="receipt">
                    <div class="bar"><span>Form ABC-2 · Received</span><span>{{ formatDate(today) }}</span></div>
                    <div class="receipt__body">
                        <div class="receipt__n" aria-hidden="true">✓</div>
                        <div>
                            <h2 id="receipt-heading" ref="receiptHeading" tabindex="-1">
                                {{ state === 'sent'
                                    ? 'Thank you, Pastor. Your reference has been sent to the Apostolic Bible College.'
                                    : 'This reference has already been sent to the Apostolic Bible College.' }}
                            </h2>
                            <p v-if="context.applicant_name">It was for {{ context.applicant_name }}. Thank you for taking the time.</p>
                            <p><router-link to="/apostolic-bible-college">About the Apostolic Bible College</router-link></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- A link that matches nothing, often one replaced by a newer
                 email, or a failure to load. Said plainly, with a way on. -->
            <section v-else-if="state === 'missing' || state === 'error'" class="success">
                <div class="receipt receipt--stop">
                    <div class="bar"><span>Form ABC-2</span><span>{{ state === 'missing' ? 'Link not valid' : 'Not loaded' }}</span></div>
                    <div class="receipt__body">
                        <div class="receipt__n" aria-hidden="true">!</div>
                        <div>
                            <h2 id="receipt-heading" ref="receiptHeading" tabindex="-1">
                                {{ state === 'missing'
                                    ? 'This link is not valid.'
                                    : 'The questionnaire could not be loaded.' }}
                            </h2>
                            <p v-if="state === 'missing'">
                                It may have been replaced by a newer email from the college. Please use the link in the
                                most recent email, or write to <span class="email">apostolicbiblecollege@upci.co.nz</span>.
                            </p>
                            <template v-else>
                                <p>Please check your connection and try again.</p>
                                <p><button class="btn" type="button" @click="load">Try again</button></p>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <template v-else-if="state === 'form'">
                <div class="intro">
                    <p>Dear Pastor,</p>
                    <p>
                        An applicant for Apostolic Bible College, <b>{{ context.applicant_name }}</b><template v-if="context.church"> of {{ context.church }}</template>, has listed you as their pastor.
                        Please take the time to fill out this short questionnaire and let us know if you approve for the
                        applicant to be a student at ABC.
                    </p>
                </div>

                <form novalidate @submit.prevent="submit">
                    <div class="errsum-wrap">
                        <ErrorSummary :errors="errors" />
                    </div>

                    <!-- 1 -->
                    <section id="section-1" class="sec" aria-labelledby="s1-h">
                        <header class="opener">
                            <div class="opener__n" aria-hidden="true">1</div>
                            <div class="opener__t">
                                <p class="opener__k">Section 1 of 3</p>
                                <h2 id="s1-h">Faithfulness</h2>
                                <p class="opener__p">Is this applicant faithful in the following? · 1.1 – 1.3</p>
                            </div>
                        </header>
                        <div class="body">
                            <div v-for="(question, i) in faithfulness" :key="question.field" :class="q(question.field)">
                                <span class="qn">1.{{ i + 1 }}</span>
                                <fieldset :aria-describedby="describedBy(question.field)">
                                    <!-- The stem is said with each item, for anyone moving
                                         from one question to the next by keyboard. -->
                                    <legend><span class="vh">Is this applicant faithful in </span>{{ question.label }}<Req /></legend>
                                    <FieldError :errors="errors" :field="question.field" />
                                    <YesNo v-model="form[question.field]" :name="question.field" />
                                </fieldset>
                            </div>
                        </div>
                    </section>

                    <!-- 2 -->
                    <section id="section-2" class="sec" aria-labelledby="s2-h">
                        <header class="opener">
                            <div class="opener__n" aria-hidden="true">2</div>
                            <div class="opener__t">
                                <p class="opener__k">Section 2 of 3</p>
                                <h2 id="s2-h">Serving</h2>
                                <p class="opener__p">One question · 2.1</p>
                            </div>
                        </header>
                        <div class="body">
                            <div :class="q('is_serving', 'serving_details')">
                                <span class="qn">2.1</span>
                                <fieldset :aria-describedby="describedBy('is_serving')">
                                    <legend>Is this applicant currently serving in some area of your church?<Req /></legend>
                                    <FieldError :errors="errors" field="is_serving" />
                                    <YesNo v-model="form.is_serving" name="is_serving" />
                                    <div v-if="form.is_serving === 'yes'" class="follow">
                                        <span class="qn">2.1a</span>
                                        <label class="l inline" for="serving_details">How are they involved?<Req /></label>
                                        <FieldError :errors="errors" field="serving_details" />
                                        <textarea id="serving_details" v-model="form.serving_details" rows="4" class="short gap" :aria-invalid="invalid('serving_details')" :aria-describedby="describedBy('serving_details')"></textarea>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </section>

                    <!-- 3 -->
                    <section id="section-3" class="sec" aria-labelledby="s3-h">
                        <header class="opener">
                            <div class="opener__n" aria-hidden="true">3</div>
                            <div class="opener__t">
                                <p class="opener__k">Section 3 of 3</p>
                                <h2 id="s3-h">Approval</h2>
                                <p class="opener__p">Then sign with your name</p>
                            </div>
                        </header>
                        <div class="body">
                            <div :class="q('approves', 'approves_level')">
                                <span class="qn">3.1</span>
                                <fieldset :aria-describedby="describedBy('approves')">
                                    <legend>Do you approve of this applicant becoming a student?<Req /></legend>
                                    <FieldError :errors="errors" field="approves" />
                                    <YesNo v-model="form.approves" name="approves" />
                                    <!-- Nested like Form ABC-1's 3.1a. Every new student
                                         starts at Certificate Level, so that is the level
                                         the applicant has asked for. -->
                                    <div v-if="form.approves === 'yes'" class="follow">
                                        <fieldset :aria-describedby="describedBy('approves_level')">
                                            <legend><span class="qn">3.1a</span>If so, are you happy for them to be enrolled at their requested level of study, {{ context.level }}?<Req /></legend>
                                            <FieldError :errors="errors" field="approves_level" />
                                            <YesNo v-model="form.approves_level" name="approves_level" />
                                        </fieldset>
                                    </div>
                                </fieldset>
                            </div>
                            <div :class="q('comments')">
                                <span class="qn">3.2</span>
                                <div>
                                    <label class="l" for="comments">Is there anything else you would like the college to know? <span class="opt-note">(optional)</span></label>
                                    <FieldError :errors="errors" field="comments" />
                                    <textarea id="comments" v-model="form.comments" rows="4" class="short" :aria-invalid="invalid('comments')" :aria-describedby="describedBy('comments')"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- The signature block, as on Form ABC-1: a name on a
                             signature line, the date beside it, and a line
                             confirming what is signed. -->
                        <div class="sig" role="group" aria-labelledby="sig-h">
                            <div class="bar"><span id="sig-h">3.3 · Signature of pastor</span><span>Form ABC-2</span></div>
                            <div class="sig__body">
                                <div :class="['sig__f', { 'is-error': errors.signed_name }]">
                                    <label for="signed_name">Your name<Req /></label>
                                    <FieldError :errors="errors" field="signed_name" />
                                    <div class="sig__line">
                                        <span class="x" aria-hidden="true">×</span>
                                        <input id="signed_name" v-model="form.signed_name" type="text" autocomplete="name" :aria-invalid="invalid('signed_name')" :aria-describedby="describedBy('signed_name', 'sig-cap-1')">
                                    </div>
                                    <p id="sig-cap-1" class="sig__cap">Printed name of pastor</p>
                                </div>
                                <div class="sig__f">
                                    <p class="sig__label">Date</p>
                                    <div class="sig__line"><span class="sig__date">{{ formatDate(today) }}</span></div>
                                    <p class="sig__cap">Date signed</p>
                                </div>
                                <p class="sig__echo" aria-live="polite">
                                    Signed by <b>{{ form.signed_name.trim() || '—' }}</b> on <b>{{ formatDate(today) }}</b>
                                </p>
                            </div>
                        </div>
                    </section>

                    <div class="privacy">
                        <b>Privacy</b>
                        Your answers go to UPCI New Zealand's Apostolic Bible College, to consider this application. They
                        are seen only by national UPCI-NZ administrators.
                    </div>

                    <div v-if="generalError" class="general-error" role="alert">{{ generalError }}</div>

                    <div class="submit">
                        <button class="btn" type="submit" :disabled="sending">
                            {{ sending ? 'Sending…' : 'Send to the college' }} <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>
            </template>

            <footer class="colophon">
                <span>Form ABC-2 · Pastor's reference</span>
                <span>Apostolic Bible College · UPCI New Zealand</span>
            </footer>
        </div>
    </div>
</template>

<script>
import { defineComponent, nextTick, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import ErrorSummary from '../components/layout/ErrorSummary.vue'
import { usePageMeta, breadcrumbSuppressed } from '../composables/usePageMeta'
import { FieldError, Req, YesNo, formatDate, localDate } from './abc-form-parts'

export default defineComponent({
    name: 'AbcPastorReference',
    components: { ErrorSummary, FieldError, Req, YesNo },
    setup() {
        const { setPageMeta } = usePageMeta()
        const route = useRoute()
        const url = `/api/abc-pastor-references/${encodeURIComponent(String(route.params.token || ''))}`

        // loading | form | sent | already | missing | error
        const state = ref('loading')
        const context = ref({})
        const form = reactive({
            faithful_attendance: '', faithful_holiness: '', faithful_tithes: '',
            is_serving: '', serving_details: '', approves: '', approves_level: '',
            comments: '', signed_name: '',
        })
        const errors = ref({})
        const generalError = ref('')
        const sending = ref(false)
        const receiptHeading = ref(null)
        const today = localDate()

        // Brandon's three, under the stem "Is this applicant faithful in the
        // following?".
        const faithfulness = [
            { field: 'faithful_attendance', label: 'Attendance to services' },
            { field: 'faithful_holiness', label: 'Holiness & modesty' },
            { field: 'faithful_tithes', label: 'Tithes and offerings' },
        ]

        const q = (...fields) => ['q', { 'is-error': fields.some((field) => errors.value[field]) }]
        const invalid = (field) => (errors.value[field] ? 'true' : null)
        const describedBy = (field, extra = null) => [errors.value[field] ? `${field}-error` : null, extra].filter(Boolean).join(' ') || null

        const focusHeading = async () => {
            window.scrollTo({ top: 0 })
            await nextTick()
            receiptHeading.value?.focus()
        }

        const load = async () => {
            state.value = 'loading'
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } })
                if (response.status === 404) {
                    state.value = 'missing'
                    return
                }
                if (!response.ok) {
                    state.value = 'error'
                    return
                }
                context.value = (await response.json()).data || {}
                form.signed_name = context.value.pastor_name || ''
                state.value = context.value.submitted ? 'already' : 'form'
            } catch (e) {
                state.value = 'error'
            }
        }

        // Only what is showing is sent, as on Form ABC-1: a follow-up
        // answered and then hidden again stays out of the request.
        const payload = () => {
            const data = { ...form }
            if (form.is_serving !== 'yes') delete data.serving_details
            if (form.approves !== 'yes') delete data.approves_level
            if (!form.comments.trim()) delete data.comments
            return data
        }

        const submit = async () => {
            errors.value = {}
            generalError.value = ''
            sending.value = true

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload()),
                })

                if (response.ok || response.status === 409) {
                    state.value = response.ok ? 'sent' : 'already'
                    await focusHeading()
                    return
                }
                if (response.status === 422) {
                    errors.value = (await response.json()).errors || {}
                    return
                }
                if (response.status === 404) {
                    state.value = 'missing'
                    await focusHeading()
                    return
                }

                generalError.value = response.status === 429
                    ? 'Too many attempts. Please wait a minute and try again.'
                    : 'Something went wrong and your reference was not sent. Please try again.'
            } catch (e) {
                generalError.value = 'Network error. Please check your connection and try again.'
            } finally {
                sending.value = false
            }
        }

        onMounted(() => {
            setPageMeta("Pastor's Reference - Apostolic Bible College", "A short reference questionnaire for a Bible college applicant's pastor.")
            // No trail: the page is reached only from an emailed link, and
            // the trail built from its address would link to
            // /apostolic-bible-college/pastor-reference, which is no page.
            // usePageMeta turns it back on when the page unmounts.
            breadcrumbSuppressed.value = true
            load()
        })

        return {
            state, context, form, errors, generalError, sending, receiptHeading, today,
            faithfulness, q, invalid, describedBy, formatDate, load, submit,
        }
    },
})
</script>

<style scoped src="./abc-form-document.css"></style>

<style scoped>
/* Form ABC-2 only. The design both college forms share is in
   abc-form-document.css. */

/* "Dear Pastor," stands on its own line, as in a letter. */
.intro p + p { margin-top: 12px; }

/* The date beside the signature is today's, not a field: the reference is
   dated when it is sent. Set like Form ABC-1's date input. */
.sig__label { display: block; font-size: 15px; font-weight: 600; line-height: 1.4; margin: 0 0 4px; min-height: 42px; }
.sig__date { display: block; height: 64px; line-height: 64px; padding: 0 2px; font-size: 22px; font-weight: 500; }

/* A link that opens nothing gets the ink bar, not the green of a receipt. */
.receipt--stop .bar { background: var(--ink); }
.receipt--stop .receipt__n { color: var(--ink); }

@media (max-width: 760px) {
    .sig__label { min-height: 0; }
    .sig__date { font-size: 20px; }
}
</style>
