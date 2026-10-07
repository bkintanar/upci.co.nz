<template>
    <!-- Direction D3, "the application as a document" (.claude/design/abc-enrolment/
         direction-approved.md): the page is set as the college's own form, Form
         ABC-1, rather than a web survey -- five numbered sections, hanging clause
         numbers, and an agreement that ends in a signature block. -->
    <div class="abc">
        <div class="doc">
            <header class="mast">
                <div class="mast__rule"></div>
                <div class="mast__ref">
                    <span>Apostolic Bible College · UPCI New Zealand</span>
                    <span>Form ABC-1 · First-time students</span>
                </div>
                <h1>New Student Enrolment <span class="sub">— Apostolic Bible College</span></h1>
                <dl v-if="!submitted" class="mast__meta">
                    <div><dt>Sections</dt><dd>Five</dd></div>
                    <div><dt>Time</dt><dd>About 10–15 minutes</dd></div>
                    <div><dt>Marked <span aria-hidden="true">*</span></dt><dd>Required answer</dd></div>
                </dl>
            </header>

            <!-- Success: a receipt in the form's own language, replacing the form. -->
            <section v-if="submitted" class="success">
                <div class="receipt">
                    <div class="bar"><span>Form ABC-1 · Received</span><span>{{ formatDate(today) }}</span></div>
                    <div class="receipt__body">
                        <div class="receipt__n" aria-hidden="true">✓</div>
                        <div>
                            <h2 id="success-heading" ref="successHeading" tabindex="-1">Thank you — your enrolment has been received.</h2>
                            <p>The Apostolic Bible College will be in touch by email.</p>
                            <p><router-link to="/apostolic-bible-college">Back to the Apostolic Bible College</router-link></p>
                        </div>
                    </div>
                </div>
            </section>

            <template v-else>
                <div class="intro">
                    <p>
                        Praise the Lord! We warmly welcome you to the Apostolic Bible School (ABC) of UPCI-NZ. We are
                        blessed to see your interest in enriching and equipping yourself for the furtherance of God's
                        kingdom. This form is for students that are enrolling to UPCI-NZ Apostolic Bible School for the
                        first time. Kindly fill them completely to avoid any delays in your application. If you have been
                        a student of Apostolic Bible School (ABC) before, kindly use the
                        <a :href="returningFormUrl" target="_blank" rel="noopener noreferrer">Enrolment Form for Returning Students</a>.
                    </p>
                </div>

                <!-- Progression of Study: was an image on the Google Form; rebuilt as
                     a list so it is readable, zoomable and translatable. -->
                <section class="prog" aria-labelledby="prog-h">
                    <div class="prog__head"><h2 id="prog-h" class="prog__h">Progression of Study</h2><span>Five levels</span></div>
                    <ol class="ladder">
                        <li
                            v-for="(level, i) in levels"
                            :key="level.n"
                            :class="['rung', { 'rung--now': i === 0 }]"
                            :aria-current="i === 0 ? 'step' : null"
                        >
                            <span v-if="i === 0" class="rung__here">You start here</span>
                            <span class="rung__n">{{ level.n }}</span>
                            <span class="rung__t">{{ level.title }}</span>
                            <span class="rung__y">{{ level.year }}</span>
                        </li>
                    </ol>
                    <p class="prog__cap">
                        All new students who enrol with UPCI-NZ ABC begin their studies at the Certificate Level, and
                        progress to higher levels accordingly after completing the required units. If you are a new
                        student to ABC but have completed a level of study with other UPCI Bible School, kindly indicate
                        it at <a href="#section-2" @click.prevent="goToSection(1)">Section 2</a>.
                    </p>
                </section>

                <!-- Running head: where am I, and how much is left. A box fills
                     once that section's required answers are all given. -->
                <nav class="runhead" aria-label="Form sections">
                    <div class="runhead__in">
                        <p class="runhead__now" aria-live="polite">
                            Section <b>{{ current + 1 }}</b> of 5<span class="t"> · {{ sections[current].title }}</span>
                        </p>
                        <ol>
                            <li v-for="(section, i) in sections" :key="section.id" :class="{ done: sectionDone[i] }">
                                <a
                                    :href="`#${section.id}`"
                                    :aria-current="i === current ? 'step' : null"
                                    :aria-label="`Section ${i + 1}: ${section.title}${sectionDone[i] ? ', complete' : ''}`"
                                    @click.prevent="goToSection(i)"
                                >{{ i + 1 }}</a>
                            </li>
                        </ol>
                    </div>
                </nav>

                <form ref="formEl" novalidate @submit.prevent="submit">
                    <div class="errsum-wrap">
                        <ErrorSummary :errors="errors" />
                    </div>

                    <!-- Honeypot: off-screen and out of the tab order, so only a bot
                         fills it. The API answers such a post with a fake success. -->
                    <div class="hp" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- 1 -->
                    <section :id="sections[0].id" class="sec" aria-labelledby="s1-h">
                        <header class="opener">
                            <div class="opener__n" aria-hidden="true">1</div>
                            <div class="opener__t">
                                <p class="opener__k">Section 1 of 5</p>
                                <h2 id="s1-h">Personal information</h2>
                                <p class="opener__p">Ten questions · 1.1 – 1.10</p>
                            </div>
                        </header>
                        <div class="body">
                            <div :class="q('full_name')">
                                <span class="qn">1.1</span>
                                <div>
                                    <label class="l" for="full_name">Full name<Req /></label>
                                    <FieldError :errors="errors" field="full_name" />
                                    <input id="full_name" v-model="form.full_name" type="text" autocomplete="name" class="w-l" :aria-invalid="invalid('full_name')" :aria-describedby="describedBy('full_name')">
                                </div>
                            </div>
                            <div :class="q('date_of_birth')">
                                <span class="qn">1.2</span>
                                <div>
                                    <label class="l" for="date_of_birth">Date of birth<Req /></label>
                                    <FieldError :errors="errors" field="date_of_birth" />
                                    <input id="date_of_birth" v-model="form.date_of_birth" type="date" :max="today" autocomplete="bday" class="w-s" :aria-invalid="invalid('date_of_birth')" :aria-describedby="describedBy('date_of_birth')">
                                </div>
                            </div>
                            <div :class="q('gender')">
                                <span class="qn">1.3</span>
                                <fieldset :aria-describedby="describedBy('gender')">
                                    <legend>Gender<Req /></legend>
                                    <FieldError :errors="errors" field="gender" />
                                    <div class="opts">
                                        <label class="opt"><input id="gender" v-model="form.gender" type="radio" name="gender" value="male">Male</label>
                                        <label class="opt"><input id="gender-female" v-model="form.gender" type="radio" name="gender" value="female">Female</label>
                                    </div>
                                </fieldset>
                            </div>
                            <div :class="q('citizenship')">
                                <span class="qn">1.4</span>
                                <div>
                                    <label class="l" for="citizenship">Citizenship<Req /></label>
                                    <FieldError :errors="errors" field="citizenship" />
                                    <input id="citizenship" v-model="form.citizenship" type="text" class="w-m" :aria-invalid="invalid('citizenship')" :aria-describedby="describedBy('citizenship')">
                                </div>
                            </div>
                            <div :class="q('address')">
                                <span class="qn">1.5</span>
                                <div>
                                    <label class="l" for="address">Address<Req /></label>
                                    <FieldError :errors="errors" field="address" />
                                    <textarea id="address" v-model="form.address" rows="3" autocomplete="street-address" class="w-l short" :aria-invalid="invalid('address')" :aria-describedby="describedBy('address')"></textarea>
                                </div>
                            </div>
                            <div :class="q('home_phone')">
                                <span class="qn">1.6</span>
                                <div>
                                    <label class="l" for="home_phone">Home phone<Req /></label>
                                    <FieldError :errors="errors" field="home_phone" />
                                    <input id="home_phone" v-model="form.home_phone" type="tel" autocomplete="tel" class="w-m" :aria-invalid="invalid('home_phone')" :aria-describedby="describedBy('home_phone')">
                                </div>
                            </div>
                            <div :class="q('mobile_phone')">
                                <span class="qn">1.7</span>
                                <div>
                                    <label class="l" for="mobile_phone">Mobile phone<Req /></label>
                                    <FieldError :errors="errors" field="mobile_phone" />
                                    <input id="mobile_phone" v-model="form.mobile_phone" type="tel" autocomplete="tel" class="w-m" :aria-invalid="invalid('mobile_phone')" :aria-describedby="describedBy('mobile_phone')">
                                </div>
                            </div>
                            <div :class="q('email')">
                                <span class="qn">1.8</span>
                                <div>
                                    <label class="l" for="email">Email<Req /></label>
                                    <FieldError :errors="errors" field="email" />
                                    <input id="email" v-model="form.email" type="email" autocomplete="email" class="w-l" :aria-invalid="invalid('email')" :aria-describedby="describedBy('email')">
                                </div>
                            </div>
                            <div :class="q('resides_in_nz', 'residence_country')">
                                <span class="qn">1.9</span>
                                <fieldset :aria-describedby="describedBy('resides_in_nz')">
                                    <legend>Are you currently residing in New Zealand?<Req /></legend>
                                    <FieldError :errors="errors" field="resides_in_nz" />
                                    <YesNo v-model="form.resides_in_nz" name="resides_in_nz" />
                                    <div v-if="form.resides_in_nz === 'no'" class="follow">
                                        <span class="qn">1.9a</span>
                                        <label class="l inline" for="residence_country">If residing in a country other than New Zealand, please specify where<Req /></label>
                                        <FieldError :errors="errors" field="residence_country" />
                                        <input id="residence_country" v-model="form.residence_country" type="text" autocomplete="country-name" class="w-m gap" :aria-invalid="invalid('residence_country')" :aria-describedby="describedBy('residence_country')">
                                    </div>
                                </fieldset>
                            </div>
                            <div :class="q('reason_for_attending')">
                                <span class="qn">1.10</span>
                                <div>
                                    <label class="l" for="reason_for_attending">Why do you want to attend the Apostolic Bible College of NZ?<Req /></label>
                                    <FieldError :errors="errors" field="reason_for_attending" />
                                    <textarea id="reason_for_attending" v-model="form.reason_for_attending" rows="5" :aria-invalid="invalid('reason_for_attending')" :aria-describedby="describedBy('reason_for_attending')"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 2 -->
                    <section :id="sections[1].id" class="sec" aria-labelledby="s2-h">
                        <header class="opener">
                            <div class="opener__n" aria-hidden="true">2</div>
                            <div class="opener__t">
                                <p class="opener__k">Section 2 of 5</p>
                                <h2 id="s2-h">Previous Bible school attendance</h2>
                                <p class="opener__p">One question · 2.1</p>
                            </div>
                        </header>
                        <div class="body">
                            <div :class="q('attended_upci_bible_school', 'transcript')">
                                <span class="qn">2.1</span>
                                <fieldset :aria-describedby="describedBy('attended_upci_bible_school')">
                                    <legend>Have you ever attended a UPCI Bible school before?<Req /></legend>
                                    <FieldError :errors="errors" field="attended_upci_bible_school" />
                                    <YesNo v-model="form.attended_upci_bible_school" name="attended_upci_bible_school" />
                                    <div v-if="form.attended_upci_bible_school === 'yes'" class="follow">
                                        <p class="help">
                                            Please attach the transcript of your previous study. If you have queries, don't
                                            hesitate to reach out to us at <span class="email">apostolicbiblecollege@upci.co.nz</span>
                                        </p>
                                        <span class="qn">2.1a</span>
                                        <label class="l inline" for="transcript">Transcript (PDF, JPG or PNG, up to 10 MB) <span class="opt-note">— optional</span></label>
                                        <FieldError :errors="errors" field="transcript" />
                                        <input id="transcript" type="file" class="file gap" accept=".pdf,.jpg,.jpeg,.png" :aria-invalid="invalid('transcript')" :aria-describedby="describedBy('transcript')" @change="pickTranscript">
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </section>

                    <!-- 3 -->
                    <section :id="sections[2].id" class="sec" aria-labelledby="s3-h">
                        <header class="opener">
                            <div class="opener__n" aria-hidden="true">3</div>
                            <div class="opener__t">
                                <p class="opener__k">Section 3 of 5</p>
                                <h2 id="s3-h">Learning challenges</h2>
                                <p class="opener__p">One question · 3.1</p>
                            </div>
                        </header>
                        <div class="body">
                            <div :class="q('has_learning_difficulties', 'learning_difficulty', 'learning_difficulty_other')">
                                <span class="qn">3.1</span>
                                <fieldset :aria-describedby="describedBy('has_learning_difficulties')">
                                    <legend>Do you have any learning difficulties?<Req /></legend>
                                    <FieldError :errors="errors" field="has_learning_difficulties" />
                                    <YesNo v-model="form.has_learning_difficulties" name="has_learning_difficulties" />
                                    <div v-if="form.has_learning_difficulties === 'yes'" class="follow">
                                        <fieldset :aria-describedby="describedBy('learning_difficulty')">
                                            <legend><span class="qn">3.1a</span>If yes, please indicate which from the list below:<Req /></legend>
                                            <FieldError :errors="errors" field="learning_difficulty" />
                                            <div class="opts">
                                                <label v-for="(label, value, i) in learningDifficulties" :key="value" class="opt">
                                                    <input :id="i === 0 ? 'learning_difficulty' : `learning_difficulty-${value}`" v-model="form.learning_difficulty" type="radio" name="learning_difficulty" :value="value">{{ label }}
                                                </label>
                                            </div>
                                            <div v-if="form.learning_difficulty === 'other'" class="follow">
                                                <span class="qn">3.1b</span>
                                                <label class="l inline" for="learning_difficulty_other">Please describe<Req /></label>
                                                <FieldError :errors="errors" field="learning_difficulty_other" />
                                                <input id="learning_difficulty_other" v-model="form.learning_difficulty_other" type="text" class="w-l gap" :aria-invalid="invalid('learning_difficulty_other')" :aria-describedby="describedBy('learning_difficulty_other')">
                                            </div>
                                        </fieldset>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </section>

                    <!-- 4 -->
                    <section :id="sections[3].id" class="sec" aria-labelledby="s4-h">
                        <header class="opener">
                            <div class="opener__n" aria-hidden="true">4</div>
                            <div class="opener__t">
                                <p class="opener__k">Section 4 of 5</p>
                                <h2 id="s4-h">Spiritual information</h2>
                                <p class="opener__p">Eight questions · 4.1 – 4.8</p>
                            </div>
                        </header>
                        <div class="body">
                            <div v-for="(question, i) in spiritualQuestions" :key="question.field" :class="q(question.field)">
                                <span class="qn">4.{{ i + 1 }}</span>
                                <fieldset :aria-describedby="describedBy(question.field)">
                                    <legend>{{ question.label }}<Req /></legend>
                                    <FieldError :errors="errors" :field="question.field" />
                                    <YesNo v-model="form[question.field]" :name="question.field" />
                                </fieldset>
                            </div>
                            <div :class="q('church_name', 'church_name_other')">
                                <span class="qn">4.5</span>
                                <fieldset :aria-describedby="describedBy('church_name')">
                                    <legend>What is the name of the church you currently attend?<Req /></legend>
                                    <FieldError :errors="errors" field="church_name" />
                                    <div class="opts opts--list">
                                        <label v-for="(church, i) in churches" :key="church.value" class="opt">
                                            <input :id="i === 0 ? 'church_name' : `church_name-${i + 1}`" v-model="form.church_name" type="radio" name="church_name" :value="church.value">
                                            <span>{{ church.name }}<small>{{ church.town }}</small></span>
                                        </label>
                                        <label class="opt">
                                            <input id="church_name-other" v-model="form.church_name" type="radio" name="church_name" value="other">
                                            <span>Other</span>
                                        </label>
                                    </div>
                                    <div v-if="form.church_name === 'other'" class="follow">
                                        <span class="qn">4.5a</span>
                                        <label class="l inline" for="church_name_other">Church name<Req /></label>
                                        <FieldError :errors="errors" field="church_name_other" />
                                        <input id="church_name_other" v-model="form.church_name_other" type="text" class="w-l gap" :aria-invalid="invalid('church_name_other')" :aria-describedby="describedBy('church_name_other')">
                                    </div>
                                </fieldset>
                            </div>
                            <div :class="q('pastor_name')">
                                <span class="qn">4.6</span>
                                <div>
                                    <label class="l" for="pastor_name">Who is your church pastor?<Req /></label>
                                    <FieldError :errors="errors" field="pastor_name" />
                                    <input id="pastor_name" v-model="form.pastor_name" type="text" class="w-l" :aria-invalid="invalid('pastor_name')" :aria-describedby="describedBy('pastor_name')">
                                </div>
                            </div>
                            <div :class="q('attendance_duration')">
                                <span class="qn">4.7</span>
                                <div>
                                    <label class="l" for="attendance_duration">How long have you attended?<Req /></label>
                                    <FieldError :errors="errors" field="attendance_duration" />
                                    <input id="attendance_duration" v-model="form.attendance_duration" type="text" class="w-m" :aria-invalid="invalid('attendance_duration')" :aria-describedby="describedBy('attendance_duration')">
                                </div>
                            </div>
                            <div :class="q('ministry_involvement')">
                                <span class="qn">4.8</span>
                                <div>
                                    <label class="l" for="ministry_involvement">Describe any area of ministry you are currently involved in.<Req /></label>
                                    <FieldError :errors="errors" field="ministry_involvement" />
                                    <textarea id="ministry_involvement" v-model="form.ministry_involvement" rows="5" :aria-invalid="invalid('ministry_involvement')" :aria-describedby="describedBy('ministry_involvement')"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 5 -->
                    <section :id="sections[4].id" class="sec" aria-labelledby="s5-h">
                        <header class="opener">
                            <div class="opener__n" aria-hidden="true">5</div>
                            <div class="opener__t">
                                <p class="opener__k">Section 5 of 5</p>
                                <h2 id="s5-h">Financial information &amp; agreement</h2>
                                <p class="opener__p">Read the terms · then sign with your printed name and date</p>
                            </div>
                        </header>
                        <div class="body">
                            <div class="q">
                                <span class="qn">5.1</span>
                                <div>
                                    <p class="fees-note">
                                        Students are encouraged to settle their fees as soon as possible. Should you wish to
                                        discuss an alternative payment plan, please contact the Bible college administrator.
                                        All students will receive an invoice with relevant fee and payment information before
                                        course begins.
                                    </p>
                                    <!-- Transcribed from the "Schedule of fees" image on the Google
                                         Form (page 6 of 7, captured 2026-10-08). Text rather than an
                                         image so it can be read, zoomed and edited. -->
                                    <table class="fees">
                                        <caption>Schedule of fees</caption>
                                        <tbody>
                                            <tr><th scope="row">Total Fees for Full Time Students (per year)</th><td>$350</td></tr>
                                            <tr><th scope="row">Total Fees for Part Time Students (per year)</th><td>$250</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="q">
                                <span class="qn">5.2</span>
                                <div>
                                    <p class="clauses-lead">I hereby certify/agree that:</p>
                                    <ol class="clauses">
                                        <li><span>the information provided on this application is complete and accurate to the best of my knowledge and the Apostolic Bible College of NZ is authorized to make whatever inquiries are necessary to certify the accuracy of my records;</span></li>
                                        <li><span>the registration fee of $50.00 is non-refundable;</span></li>
                                        <li><span>the course material provided to me is the property of the Apostolic Bible College of NZ until such time that I pay the total fees for the calendar year, and that if I withdraw from the programme prior to the full payment of fees, that the course material must be returned to the Bible School;</span></li>
                                        <li><span>I will adhere to the student requirements as outlined in the Student Handbook.</span></li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- The signature block: printed name on a signature line, the
                             date beside it, and a live line confirming what is signed. -->
                        <div class="sig" role="group" aria-labelledby="sig-h">
                            <div class="bar"><span id="sig-h">5.3 · Signature of applicant</span><span>Form ABC-1</span></div>
                            <div class="sig__body">
                                <div :class="['sig__f', { 'is-error': errors.agreement_name }]">
                                    <label for="agreement_name">If you agree, please print your full name<Req /></label>
                                    <FieldError :errors="errors" field="agreement_name" />
                                    <div class="sig__line">
                                        <span class="x" aria-hidden="true">×</span>
                                        <input id="agreement_name" v-model="form.agreement_name" type="text" autocomplete="name" :aria-invalid="invalid('agreement_name')" :aria-describedby="describedBy('agreement_name', 'sig-cap-1')">
                                    </div>
                                    <p id="sig-cap-1" class="sig__cap">Printed full name of applicant</p>
                                </div>
                                <div :class="['sig__f', { 'is-error': errors.agreement_date }]">
                                    <label for="agreement_date">If you agree, please select today's date<Req /></label>
                                    <FieldError :errors="errors" field="agreement_date" />
                                    <div class="sig__line">
                                        <input id="agreement_date" v-model="form.agreement_date" type="date" class="date" :aria-invalid="invalid('agreement_date')" :aria-describedby="describedBy('agreement_date', 'sig-cap-2')">
                                    </div>
                                    <p id="sig-cap-2" class="sig__cap">Date signed</p>
                                </div>
                                <p class="sig__echo" aria-live="polite">
                                    Signed by <b>{{ form.agreement_name.trim() || '—' }}</b> on <b>{{ formatDate(form.agreement_date) }}</b>
                                </p>
                            </div>
                        </div>
                    </section>

                    <div class="privacy">
                        <b>Privacy</b>
                        The information you provide is collected by UPCI New Zealand's Apostolic Bible College to process
                        your enrolment. It is seen only by national UPCI-NZ administrators, and you can ask to see or
                        correct it by emailing <span class="email">apostolicbiblecollege@upci.co.nz</span>.
                    </div>

                    <div v-if="generalError" class="general-error" role="alert">{{ generalError }}</div>

                    <div class="submit">
                        <button class="btn" type="submit" :disabled="sending">
                            {{ sending ? 'Sending…' : 'Submit enrolment' }} <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>
            </template>

            <footer class="colophon">
                <span>Form ABC-1 · First-time students</span>
                <span>Apostolic Bible College · UPCI New Zealand</span>
            </footer>
        </div>
    </div>
</template>

<script>
import { computed, defineComponent, h, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import ErrorSummary from '../components/layout/ErrorSummary.vue'
import { usePageMeta } from '../composables/usePageMeta'

/**
 * The church list, in the Google Form's order. MUST stay identical to
 * App\Models\AbcEnrolment::CHURCHES: the API only accepts these exact
 * strings (AbcEnrolmentNewStudentPageTest checks the two match).
 */
const CHURCHES = [
    'Apostolic Life Church (Tauranga)',
    'Apostolics of Christchurch (Christchurch)',
    'Church Triumphant Wellington (Wellington)',
    'Daystar Fellowship (Whangarei)',
    'El Shaddai- Hindi Speaking Fellowship (Auckland)',
    'Grace Fellowship (Hamilton)',
    'New Zealand Family in Christ (Auckland)',
    'Pentecostals of Rangiora (Rangiora)',
    'Solid Foundation Church (Hastings)',
    'Southside Pentecostal Fellowship (Auckland)',
    'Storehouse Chapel (Hamilton)',
    'Walk by Faith (Auckland)',
    'House of Mercy (Tokoroa)',
]

// "Required" said in words as well as the asterisk, for screen readers.
const Req = () => [
    h('span', { class: 'req', 'aria-hidden': 'true' }, '*'),
    h('span', { class: 'vh' }, ' (required)'),
]

// The server's first message for a field, id'd so the input can point at it.
const FieldError = (props) => props.errors?.[props.field]?.length
    ? h('p', { class: 'err-msg', id: `${props.field}-error` }, [h('span', { class: 'vh' }, 'Error: '), props.errors[props.field][0]])
    : null
FieldError.props = ['errors', 'field']

// Yes/No radios. The first radio carries the field name as its id, so the
// error summary's #field link lands on the group.
const YesNo = (props, { emit }) => h('div', { class: 'opts' }, ['yes', 'no'].map((value) =>
    h('label', { class: 'opt' }, [
        h('input', {
            id: value === 'yes' ? props.name : `${props.name}-no`,
            type: 'radio',
            name: props.name,
            value,
            checked: props.modelValue === value,
            onChange: () => emit('update:modelValue', value),
        }),
        value === 'yes' ? 'Yes' : 'No',
    ])
))
YesNo.props = ['modelValue', 'name']
YesNo.emits = ['update:modelValue']

const localDate = (d = new Date()) => {
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

export default defineComponent({
    name: 'AbcNewStudentEnrolment',
    components: { ErrorSummary, FieldError, Req, YesNo },
    setup() {
        const { setPageMeta } = usePageMeta()

        // Local date, not toISOString(): that is UTC, which is yesterday in
        // New Zealand every morning.
        const today = localDate()

        const form = reactive({
            full_name: '', date_of_birth: '', gender: '', citizenship: '', address: '',
            home_phone: '', mobile_phone: '', email: '', resides_in_nz: '', residence_country: '',
            reason_for_attending: '', attended_upci_bible_school: '', has_learning_difficulties: '',
            learning_difficulty: '', learning_difficulty_other: '', wants_ministerial_licence: '',
            read_articles_of_faith: '', baptised_in_jesus_name: '', received_holy_ghost: '',
            church_name: '', church_name_other: '', pastor_name: '', attendance_duration: '',
            ministry_involvement: '', agreement_name: '', agreement_date: today, website: '',
        })
        const transcript = ref(null)
        const errors = ref({})
        const generalError = ref('')
        const sending = ref(false)
        const submitted = ref(false)
        const successHeading = ref(null)

        const sections = [
            { id: 'section-1', title: 'Personal information' },
            { id: 'section-2', title: 'Previous Bible school attendance' },
            { id: 'section-3', title: 'Learning challenges' },
            { id: 'section-4', title: 'Spiritual information' },
            { id: 'section-5', title: 'Financial information & agreement' },
        ]

        const levels = [
            { n: '01', title: 'Certificate Level', year: '(Year 1)' },
            { n: '02', title: 'Intermediate Diploma', year: '(Year 1)' },
            { n: '03', title: 'Intermediate Diploma', year: '(Year 2)' },
            { n: '04', title: 'Full Diploma', year: '(Year 1)' },
            { n: '05', title: 'Full Diploma', year: '(Year 2)' },
        ]

        const churches = CHURCHES.map((value) => {
            const match = value.match(/^(.*) \(([^)]+)\)$/)
            return { value, name: match ? match[1] : value, town: match ? match[2] : '' }
        })

        const learningDifficulties = {
            hearing: 'Hearing',
            vision: 'Vision',
            writing: 'Writing',
            english: 'Using English in learning',
            other: 'Other',
        }

        const spiritualQuestions = [
            { field: 'wants_ministerial_licence', label: 'Do you desire to become a licensed minister under UPCI-NZ?' },
            { field: 'read_articles_of_faith', label: 'Have you read and understood the Articles of Faith?' },
            { field: 'baptised_in_jesus_name', label: 'Have you been baptised in the name of Jesus Christ?' },
            { field: 'received_holy_ghost', label: 'Have you received the Holy Ghost by the evidence of tongues?' },
        ]

        // Which answers each section needs right now. Follow-up questions count
        // only while they are showing, matching the API's exclude_unless rules.
        const requiredFields = computed(() => [
            [
                'full_name', 'date_of_birth', 'gender', 'citizenship', 'address', 'home_phone',
                'mobile_phone', 'email', 'resides_in_nz', 'reason_for_attending',
                ...(form.resides_in_nz === 'no' ? ['residence_country'] : []),
            ],
            ['attended_upci_bible_school'],
            [
                'has_learning_difficulties',
                ...(form.has_learning_difficulties === 'yes' ? ['learning_difficulty'] : []),
                ...(form.has_learning_difficulties === 'yes' && form.learning_difficulty === 'other' ? ['learning_difficulty_other'] : []),
            ],
            [
                ...spiritualQuestions.map((question) => question.field), 'church_name',
                ...(form.church_name === 'other' ? ['church_name_other'] : []),
                'pastor_name', 'attendance_duration', 'ministry_involvement',
            ],
            ['agreement_name', 'agreement_date'],
        ])

        const sectionDone = computed(() => requiredFields.value.map(
            (fields) => fields.every((field) => String(form[field] ?? '').trim() !== '')
        ))

        // Running head follows the reader: the last section whose top has
        // passed just under the sticky site navbar and running head.
        const current = ref(0)
        const onScroll = () => {
            let index = 0
            sections.forEach((section, i) => {
                const el = document.getElementById(section.id)
                if (el && el.getBoundingClientRect().top < 200) index = i
            })
            current.value = index
        }

        const goToSection = (index) => {
            const el = document.getElementById(sections[index].id)
            el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }

        const q = (...fields) => ['q', { 'is-error': fields.some((field) => errors.value[field]) }]
        const invalid = (field) => (errors.value[field] ? 'true' : null)
        const describedBy = (field, extra = null) => [errors.value[field] ? `${field}-error` : null, extra].filter(Boolean).join(' ') || null

        const formatDate = (value) => {
            if (!value) return '—'
            const [y, m, d] = value.split('-').map(Number)
            return new Date(y, m - 1, d).toLocaleDateString('en-NZ', { day: 'numeric', month: 'long', year: 'numeric' })
        }

        const pickTranscript = (event) => {
            const file = event.target.files?.[0] ?? null
            transcript.value = file
            const { transcript: _, ...rest } = errors.value
            errors.value = file && file.size > 10 * 1024 * 1024
                ? { ...rest, transcript: ['The transcript must be 10 MB or smaller.'] }
                : rest
        }

        // Only what is showing is sent. A follow-up answered and then hidden
        // again would be dropped by the API anyway; this keeps it out of the
        // request in the first place.
        const buildPayload = () => {
            const visible = new Set(requiredFields.value.flat())
            const always = ['website']
            const data = new FormData()
            Object.entries(form).forEach(([key, value]) => {
                if ((visible.has(key) || always.includes(key)) && value !== '') data.append(key, value)
            })
            if (form.attended_upci_bible_school === 'yes' && transcript.value) {
                data.append('transcript', transcript.value)
            }
            return data
        }

        const submit = async () => {
            errors.value = {}
            generalError.value = ''
            sending.value = true

            try {
                // No Content-Type: the browser sets the multipart boundary.
                const response = await fetch('/api/abc-enrolments', {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: buildPayload(),
                })

                if (response.ok) {
                    submitted.value = true
                    window.scrollTo({ top: 0 })
                    await nextTick()
                    successHeading.value?.focus()
                    return
                }

                if (response.status === 422) {
                    const body = await response.json()
                    errors.value = body.errors || {}
                    return
                }

                generalError.value = response.status === 429
                    ? 'Too many attempts. Please wait a minute and try again.'
                    : response.status === 413
                        ? 'That transcript is too large to send. Please choose a file of 10 MB or less.'
                        : 'Something went wrong and your enrolment was not sent. Please try again.'
            } catch (e) {
                generalError.value = 'Network error. Please check your connection and try again.'
            } finally {
                sending.value = false
            }
        }

        onMounted(() => {
            setPageMeta('New Student Enrolment - Apostolic Bible College', 'Enrol as a first-time student at the Apostolic Bible College of UPCI New Zealand.')
            window.addEventListener('scroll', onScroll, { passive: true })
        })
        onBeforeUnmount(() => window.removeEventListener('scroll', onScroll))

        return {
            form, errors, generalError, sending, submitted, successHeading, today,
            sections, levels, churches, learningDifficulties, spiritualQuestions,
            sectionDone, current, goToSection, q, invalid, describedBy,
            formatDate, pickTranscript, submit,
            returningFormUrl: 'https://forms.gle/9nEDxXdJgGbtwr5m9',
        }
    },
})
</script>

<style scoped>
/* Ported from design-demos/D3-application-document.html. Brand tokens only;
   error red is for error states and nowhere else. */
.abc {
    --ink: oklch(0.18 0.01 150);
    --paper: oklch(0.98 0.005 95);
    --green-100: oklch(0.93 0.03 143);
    --green-700: oklch(0.47 0.09 143);
    --green-900: oklch(0.34 0.07 143);
    --clay-600: oklch(0.62 0.12 55);
    --grey-200: oklch(0.90 0.005 95);
    --grey-400: oklch(0.72 0.005 95);
    --grey-600: oklch(0.55 0.005 95);
    --error: oklch(0.52 0.17 27);
    --white: #fff;
    --gutter: 72px;   /* hanging clause-number column */
    --measure: 880px;
    --field: 52px;
    --sticky-top: 80px; /* the site navbar is sticky and h-20 */

    background: var(--paper);
    color: var(--ink);
    font-size: 17px;
    line-height: 1.5;
    padding-bottom: 96px;
}
.abc a { color: var(--green-900); text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 3px; }
.abc a:hover { color: var(--ink); }
.abc :focus-visible { outline: 3px solid var(--green-700); outline-offset: 2px; }
:deep(.vh) { position: absolute !important; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.email { font-weight: 600; user-select: all; }

.doc { max-width: var(--measure); margin: 0 auto; padding: 0 24px; }

/* Masthead */
.mast { padding-top: 56px; }
.mast__rule { height: 6px; background: var(--ink); }
.mast__ref { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 10px 0; border-bottom: 1px solid var(--ink); font-size: 14px; letter-spacing: .06em; text-transform: uppercase; font-weight: 600; }
.mast__ref span:last-child { color: var(--green-900); }
.mast h1 { margin: 48px 0 0; font-weight: 800; font-size: clamp(44px, 7vw, 80px); line-height: .98; letter-spacing: -.025em; text-wrap: balance; }
.mast h1 .sub { display: block; margin-top: 14px; font-size: .46em; line-height: 1.1; font-weight: 300; letter-spacing: -.01em; color: var(--green-700); }
.mast__meta { display: grid; grid-template-columns: repeat(3, auto); justify-content: start; gap: 0 40px; margin: 40px 0 0; padding: 12px 0; border-top: 1px solid var(--grey-400); border-bottom: 1px solid var(--grey-400); font-size: 14px; }
.mast__meta dt { color: var(--grey-600); font-weight: 500; }
.mast__meta dd { margin: 0; font-weight: 600; }
.intro { margin: 40px 0 0; font-size: 20px; line-height: 1.55; max-width: 44em; }
.intro p { margin: 0; }

/* Progression ladder */
.prog { margin: 72px 0 0; }
.prog__head { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1px solid var(--ink); padding-bottom: 8px; font-size: 14px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
.prog__h { margin: 0; font: inherit; letter-spacing: inherit; }
.prog__head span { font-weight: 500; color: var(--grey-600); letter-spacing: .02em; text-transform: none; }
.ladder { list-style: none; margin: 0; padding: 56px 0 0; display: grid; grid-template-columns: repeat(5, 1fr); align-items: end; position: relative; }
.ladder::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 2px; background: var(--ink); }
.ladder::before { content: ""; position: absolute; right: -2px; bottom: -6px; border-left: 12px solid var(--ink); border-top: 7px solid transparent; border-bottom: 7px solid transparent; }
.rung { position: relative; height: var(--h); border-left: 1px solid var(--ink); border-top: 2px solid var(--ink); padding: 12px 14px 16px; background: var(--green-100); display: flex; flex-direction: column; gap: 2px; margin-right: 6px; }
.rung:nth-child(1) { --h: 104px; }
.rung:nth-child(2) { --h: 136px; }
.rung:nth-child(3) { --h: 168px; }
.rung:nth-child(4) { --h: 200px; }
.rung:nth-child(5) { --h: 232px; margin-right: 18px; }
.rung__n { font-size: 13px; font-weight: 600; font-variant-numeric: tabular-nums; color: var(--green-900); }
.rung__t { font-size: 16px; font-weight: 700; line-height: 1.2; }
.rung__y { font-size: 14px; font-weight: 500; }
.rung--now { background: var(--green-700); color: var(--paper); border-color: var(--ink); }
.rung--now .rung__n { color: var(--paper); }
.rung__here { position: absolute; left: -1px; bottom: 100%; margin-bottom: 10px; font-size: 14px; font-weight: 700; white-space: nowrap; display: flex; align-items: center; gap: 8px; color: var(--ink); }
.rung__here::before { content: ""; width: 10px; height: 10px; background: var(--clay-600); display: block; }
.prog__cap { margin: 20px 0 0; font-size: 15px; line-height: 1.55; max-width: 46em; }

/* Running head */
.runhead { position: sticky; top: var(--sticky-top); z-index: 5; background: var(--paper); margin-top: 88px; border-bottom: 1px solid var(--ink); }
.runhead__in { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 0; font-size: 14px; }
.runhead__now { margin: 0; font-weight: 700; }
.runhead__now b { font-weight: 800; font-variant-numeric: tabular-nums; }
.runhead ol { list-style: none; margin: 0; padding: 0; display: flex; gap: 4px; }
.runhead li a { display: block; width: 32px; height: 28px; line-height: 28px; text-align: center; font-size: 13px; font-weight: 700; text-decoration: none; color: var(--ink); border: 1px solid var(--grey-600); background: var(--white); }
.runhead li.done a { background: var(--green-100); border-color: var(--green-700); }
.runhead li a[aria-current="step"] { background: var(--ink); color: var(--paper); border-color: var(--ink); }

/* Error summary sits where D3 put it: top of the form. Focusing it scrolls
   it to the top of the viewport, so clear the sticky navbar and running head. */
.errsum-wrap { margin-top: 40px; }
.errsum-wrap :deep([role="alert"]) { scroll-margin-top: calc(var(--sticky-top) + 64px); }

/* Honeypot */
.hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

/* Section opener */
.sec { margin-top: 104px; scroll-margin-top: calc(var(--sticky-top) + 64px); }
.sec:first-of-type { margin-top: 64px; }
.opener { display: grid; grid-template-columns: var(--gutter) 1fr; align-items: end; border-top: 2px solid var(--ink); padding-top: 8px; }
.opener__n { font-size: 128px; line-height: .78; font-weight: 300; letter-spacing: -.05em; color: var(--green-700); font-variant-numeric: tabular-nums; margin-left: -6px; padding-top: 16px; }
.opener__t { padding-bottom: 4px; }
.opener__k { font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green-900); margin: 0 0 6px; }
.opener h2 { margin: 0; font-size: 36px; line-height: 1.08; font-weight: 800; letter-spacing: -.015em; text-wrap: balance; }
.opener__p { margin: 8px 0 0; font-size: 16px; color: var(--grey-600); font-weight: 500; }
.opener + .body { border-top: 1px solid var(--grey-400); margin-top: 24px; }

/* Questions */
.q { display: grid; grid-template-columns: var(--gutter) 1fr; padding: 24px 0; border-bottom: 1px solid var(--grey-200); scroll-margin-top: calc(var(--sticky-top) + 64px); }
.q:last-child { border-bottom: 0; }
.q > * { min-width: 0; }
.qn { font-size: 14px; font-weight: 600; color: var(--grey-600); font-variant-numeric: tabular-nums; padding-top: 2px; }
.l, legend { display: block; font-size: 16px; font-weight: 600; line-height: 1.4; margin: 0 0 10px; padding: 0; max-width: 40em; }
.l.inline { display: inline; }
:deep(.req) { color: var(--ink); font-weight: 700; margin-left: 2px; }
fieldset { border: 0; margin: 0; padding: 0; min-width: 0; }
.help { font-size: 15px; margin: 0 0 12px; max-width: 40em; }
.opt-note { font-weight: 500; color: var(--grey-600); }

input[type=text], input[type=email], input[type=tel], input[type=date], textarea {
    display: block; width: 100%; height: var(--field); padding: 0 14px; font: inherit; font-size: 17px; color: var(--ink);
    background: var(--white); border: 1px solid var(--grey-600); border-radius: 2px; appearance: none; -webkit-appearance: none;
}
textarea { height: auto; min-height: 128px; padding: 12px 14px; line-height: 1.5; resize: vertical; }
textarea.short { min-height: 104px; }
input:focus-visible, textarea:focus-visible { outline: 3px solid var(--green-700); outline-offset: 1px; border-color: var(--green-700); }
.w-s { max-width: 240px; }
.w-m { max-width: 400px; }
.w-l { max-width: 560px; }
.gap { margin-top: 10px; }

/* Options */
:deep(.opts) { display: flex; flex-wrap: wrap; gap: 8px; }
:deep(.opt) { position: relative; display: flex; align-items: center; gap: 12px; min-height: 52px; min-width: 132px; padding: 12px 18px 12px 14px; background: var(--white); border: 1px solid var(--grey-600); border-radius: 2px; cursor: pointer; font-size: 17px; line-height: 1.3; }
:deep(.opt input) { width: 22px; height: 22px; margin: 0; accent-color: var(--green-700); flex: none; }
:deep(.opt:has(input:checked)) { border-color: var(--green-700); box-shadow: inset 0 0 0 1px var(--green-700); background: var(--green-100); }
:deep(.opt:has(input:focus-visible)) { outline: 3px solid var(--green-700); outline-offset: 2px; }
.opts--list { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.opts--list .opt { min-width: 0; font-size: 16px; }
.opt small { display: block; font-size: 14px; color: var(--grey-600); font-weight: 500; }

/* Follow-ups: numbered sub-clauses, not callout boxes */
.follow { margin-top: 24px; padding-top: 20px; border-top: 1px dashed var(--grey-400); }
.follow .qn { display: inline-block; margin-right: 8px; }
.follow .follow { margin-top: 20px; }
.file { display: block; font-size: 16px; padding: 12px; background: var(--white); border: 1px dashed var(--grey-600); max-width: 560px; width: 100%; }

/* Field errors */
:deep(.err-msg) { color: var(--error); font-weight: 700; font-size: 15px; margin: 0 0 8px; }
.is-error > .qn { color: var(--error); }
.is-error input[aria-invalid="true"], .is-error textarea[aria-invalid="true"] { border: 2px solid var(--error); }
/* Only the options of the group that failed: aria-describedby is set on a
   fieldset exactly when its own field has an error. */
fieldset[aria-describedby] > :deep(.opts) > .opt { border: 2px solid var(--error); }

/* Section 5 */
.fees-note { font-size: 17px; max-width: 40em; margin: 0; }
.fees { margin: 24px 0 0; width: 100%; max-width: 560px; border-collapse: collapse; background: var(--white); border: 1px solid var(--ink); font-size: 16px; }
.fees caption { text-align: left; padding: 0 0 8px; font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.fees th, .fees td { padding: 12px 16px; border-top: 1px solid var(--grey-400); vertical-align: baseline; }
.fees tr:first-child th, .fees tr:first-child td { border-top: 0; }
.fees th { text-align: left; font-weight: 600; }
.fees td { text-align: right; font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; border-left: 1px solid var(--grey-400); }
.clauses-lead { font-weight: 700; font-size: 18px; margin: 0 0 12px; }
.clauses { list-style: none; margin: 0; padding: 0; counter-reset: c; }
.clauses li { counter-increment: c; display: grid; grid-template-columns: 40px 1fr; padding: 14px 0; border-top: 1px solid var(--grey-200); font-size: 16px; line-height: 1.55; max-width: 42em; }
.clauses li::before { content: "(" counter(c) ")"; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--green-900); }

/* The signature block */
.bar { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 10px 20px; background: var(--ink); color: var(--paper); font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.sig { margin: 48px 0 0 var(--gutter); background: var(--white); border: 1px solid var(--ink); }
.sig__body { padding: 32px 28px 28px; display: grid; grid-template-columns: 1.7fr 1fr; gap: 32px 40px; }
.sig__body > * { min-width: 0; }
.sig__f label { display: block; font-size: 15px; font-weight: 600; line-height: 1.4; margin: 0 0 4px; min-height: 42px; }
.sig__line { display: flex; align-items: flex-end; gap: 10px; border-bottom: 2px solid var(--ink); }
.sig__line .x { font-size: 28px; font-weight: 300; line-height: 1; padding-bottom: 10px; color: var(--grey-600); }
.sig__line input { border: 0; border-radius: 0; height: 64px; padding: 0 2px; font-size: 28px; font-weight: 500; font-style: italic; letter-spacing: -.005em; background: transparent; }
.sig__line input.date { font-size: 22px; font-style: normal; }
.sig__line input:focus-visible { outline: none; }
.sig__line:focus-within { border-bottom-color: var(--green-700); box-shadow: 0 3px 0 0 var(--green-700); }
.sig__f.is-error .sig__line { border-bottom: 3px solid var(--error); }
.sig__f.is-error .sig__line input { border: 0; }
.sig__cap { margin: 8px 0 0; font-size: 12px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--grey-600); }
.sig__echo { grid-column: 1 / -1; margin: 0; padding-top: 20px; border-top: 1px solid var(--grey-200); font-size: 16px; }

/* Submit */
.privacy { margin: 40px 0 0 var(--gutter); font-size: 15px; max-width: 42em; padding-top: 16px; border-top: 1px solid var(--grey-400); }
.privacy b { display: block; font-size: 13px; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 4px; }
.general-error { margin: 24px 0 0 var(--gutter); padding: 12px 16px; border-left: 4px solid var(--error); background: var(--white); font-size: 16px; }
.submit { margin: 28px 0 0 var(--gutter); display: flex; align-items: center; gap: 24px; flex-wrap: wrap; }
.btn { font: inherit; font-size: 18px; font-weight: 700; color: var(--paper); background: var(--green-700); border: 0; border-radius: 2px; min-height: 60px; padding: 0 36px; cursor: pointer; display: inline-flex; align-items: center; gap: 14px; }
.btn:hover { background: var(--green-900); }
.btn:disabled { opacity: .6; cursor: not-allowed; }
.btn span { font-weight: 400; }
.colophon { margin: 72px 0 0; border-top: 6px solid var(--ink); padding-top: 10px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; font-size: 13px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }

/* Success receipt */
.success { margin: 64px 0 0; }
.receipt { border: 1px solid var(--ink); background: var(--white); }
.receipt .bar { background: var(--green-700); }
.receipt__body { padding: 40px 32px 36px; display: grid; grid-template-columns: var(--gutter) 1fr; }
.receipt__n { font-size: 96px; font-weight: 300; line-height: .8; color: var(--green-700); }
.receipt h2 { margin: 0; font-size: 32px; line-height: 1.2; font-weight: 800; letter-spacing: -.01em; max-width: 20em; }
.receipt h2:focus { outline: none; }
.receipt p { margin: 16px 0 0; font-size: 18px; }

/* Phone */
@media (max-width: 760px) {
    .abc { --gutter: 0px; font-size: 16px; }
    .doc { padding: 0 16px; }
    .mast { padding-top: 32px; }
    .mast h1 { margin-top: 32px; }
    .mast__meta { grid-template-columns: auto 1fr; gap: 4px 16px; }
    .mast__meta div { display: contents; }
    .intro { font-size: 18px; margin-top: 28px; }
    .prog { margin-top: 56px; }
    .ladder { grid-template-columns: 1fr; padding: 44px 0 18px; align-items: stretch; gap: 6px; }
    .ladder::after { top: 44px; bottom: 18px; left: 0; right: auto; width: 2px; height: auto; }
    .ladder::before { left: -6px; right: auto; bottom: 6px; border-left: 7px solid transparent; border-right: 7px solid transparent; border-top: 12px solid var(--ink); border-bottom: 0; }
    .rung { height: auto; margin: 0 0 0 calc(var(--i) * 14px + 12px) !important; border-top: 0; border-bottom: 2px solid var(--ink); flex-direction: row; flex-wrap: wrap; align-items: baseline; gap: 4px 10px; padding: 12px 14px; }
    .rung:nth-child(1) { --i: 0; }
    .rung:nth-child(2) { --i: 1; }
    .rung:nth-child(3) { --i: 2; }
    .rung:nth-child(4) { --i: 3; }
    .rung:nth-child(5) { --i: 4; }
    .rung__n { width: 24px; }
    .rung__here { left: 12px; margin-bottom: 8px; }
    .runhead { margin-top: 64px; }
    .runhead li a { width: 30px; }
    .runhead__now .t { display: none; }
    .sec { margin-top: 80px; }
    .opener { grid-template-columns: auto 1fr; gap: 16px; }
    .opener__n { font-size: 88px; margin-left: -4px; }
    .opener h2 { font-size: 28px; }
    .opener__p { font-size: 15px; }
    .q { display: block; padding: 20px 0; }
    .q > .qn { display: block; margin-bottom: 4px; font-size: 13px; }
    :deep(.opts .opt) { flex: 1 1 120px; }
    .opts--list { grid-template-columns: 1fr; }
    .sig { margin-left: 0; }
    .sig__body { grid-template-columns: 1fr; padding: 24px 18px; }
    .sig__f label { min-height: 0; }
    .sig__line input { font-size: 24px; }
    .privacy, .submit, .general-error { margin-left: 0; }
    .btn { width: 100%; justify-content: center; }
    .receipt__body { grid-template-columns: 1fr; padding: 28px 20px; }
    .receipt__n { font-size: 72px; margin-bottom: 16px; }
    .receipt h2 { font-size: 26px; }
}

@media (prefers-reduced-motion: reduce) {
    .sec, .q { scroll-behavior: auto; }
}
</style>
