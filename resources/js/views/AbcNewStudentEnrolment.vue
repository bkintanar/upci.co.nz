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
                            <p>The Apostolic Bible College will be in touch by email. Your pastor will be emailed a short reference questionnaire.</p>
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
                            <!-- A dropdown rather than fourteen boxes (owner, 2026-10-17).
                                 Choosing a church fills in 4.6 when the college has that
                                 church's pastor on file. -->
                            <div :class="q('church_name', 'church_name_other')">
                                <span class="qn">4.5</span>
                                <div>
                                    <label class="l" for="church_name">What is the name of the church you currently attend?<Req /></label>
                                    <FieldError :errors="errors" field="church_name" />
                                    <select id="church_name" v-model="form.church_name" class="w-l" :aria-invalid="invalid('church_name')" :aria-describedby="describedBy('church_name')">
                                        <option value="" disabled>Select your church</option>
                                        <option v-for="church in churches" :key="church.value" :value="church.value">{{ church.name }}, {{ church.town }}</option>
                                        <option value="other">Other (not listed)</option>
                                    </select>
                                    <div v-if="form.church_name === 'other'" class="follow">
                                        <span class="qn">4.5a</span>
                                        <label class="l inline" for="church_name_other">Church name<Req /></label>
                                        <FieldError :errors="errors" field="church_name_other" />
                                        <input id="church_name_other" v-model="form.church_name_other" type="text" class="w-l gap" :aria-invalid="invalid('church_name_other')" :aria-describedby="describedBy('church_name_other')">
                                    </div>
                                </div>
                            </div>
                            <div :class="q('pastor_name', 'pastor_email')">
                                <span class="qn">4.6</span>
                                <div>
                                    <label class="l" for="pastor_name">Who is your church pastor?<Req /></label>
                                    <p v-if="pastorFilled" id="pastor_name-hint" class="help">Filled in from your church's details. Change it if someone else is your pastor.</p>
                                    <FieldError :errors="errors" field="pastor_name" />
                                    <input id="pastor_name" v-model="form.pastor_name" type="text" class="w-l" autocomplete="off" :aria-invalid="invalid('pastor_name')" :aria-describedby="describedBy('pastor_name', pastorFilled ? 'pastor_name-hint' : null)">
                                    <!-- Says aloud what the dropdown just filled in, without
                                         moving focus off it. In the page from the start, so
                                         screen readers are already listening. -->
                                    <p class="vh" role="status" aria-live="polite">{{ pastorStatus }}</p>
                                    <!-- The college emails the pastor a reference questionnaire
                                         (Form ABC-2). Asked only when it has no address for the
                                         pastor named above. -->
                                    <div v-if="needsPastorEmail" class="follow">
                                        <span class="qn">4.6a</span>
                                        <label class="l inline" for="pastor_email">Your pastor's email address<Req /></label>
                                        <p id="pastor_email-hint" class="help gap">The college will email your pastor a short reference questionnaire.</p>
                                        <FieldError :errors="errors" field="pastor_email" />
                                        <input id="pastor_email" v-model="form.pastor_email" type="email" class="w-l" autocomplete="off" :aria-invalid="invalid('pastor_email')" :aria-describedby="describedBy('pastor_email', 'pastor_email-hint')">
                                    </div>
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
import { computed, defineComponent, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import ErrorSummary from '../components/layout/ErrorSummary.vue'
import { usePageMeta } from '../composables/usePageMeta'
import { FieldError, Req, YesNo, formatDate, localDate } from './abc-form-parts'

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

// Req, FieldError, YesNo, localDate and formatDate are shared with Form
// ABC-2, the pastor's reference (abc-form-parts.js).

// Whether two names are the same pastor, as people write them ("Pastor
// Chester Kebasen", "ps. chester kebasen"). Mirrors AbcEnrolment::samePastor,
// which decides on the server whether the pastor's email is on file.
const pastorKey = (name) => String(name || '').toLowerCase()
    .replace(/[^\p{L}\p{N}\s]/gu, '').replace(/\s+/g, ' ').trim()
    .replace(/^(?:(?:pastor|ps|rev|reverend)\s+)+/, '')
const samePastor = (a, b) => pastorKey(a) !== '' && pastorKey(a) === pastorKey(b)

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
            church_name: '', church_name_other: '', pastor_name: '', pastor_email: '', attendance_duration: '',
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

        // Each listed church's pastor, from the CMS: { "Grace Fellowship
        // (Hamilton)": { pastor_name, pastor_on_file } }. Names only; the
        // college never publishes a pastor's email address.
        const pastors = ref({})
        const suggestedPastor = computed(() => pastors.value[form.church_name] || null)

        // The name last filled in from the dropdown. A name the applicant
        // typed themselves is never overwritten.
        const lastFilled = ref('')
        const pastorFilled = computed(() => lastFilled.value !== '' && form.pastor_name === lastFilled.value)
        const pastorStatus = ref('')

        watch(() => form.church_name, () => {
            if (form.pastor_name.trim() !== '' && form.pastor_name !== lastFilled.value) return
            const name = suggestedPastor.value?.pastor_name || ''
            form.pastor_name = name
            lastFilled.value = name
            pastorStatus.value = name ? `Pastor filled in: ${name}` : ''
        })

        // 4.6a, the pastor's email, whenever the college cannot reach the
        // pastor named in 4.6 from its own records: Other, a church with no
        // pastor email on file, or someone other than the pastor on file. The
        // API applies the same rule, and its error shows the question too.
        const needsPastorEmail = computed(() => {
            if (errors.value.pastor_email) return true
            if (!form.church_name) return false
            const onFile = suggestedPastor.value
            return form.church_name === 'other' || !onFile?.pastor_on_file || !samePastor(form.pastor_name, onFile.pastor_name)
        })

        const loadPastors = async () => {
            try {
                const response = await fetch('/api/abc-enrolments/pastors', { headers: { Accept: 'application/json' } })
                if (response.ok) pastors.value = (await response.json()).data || {}
            } catch (e) {
                // Without the list the form still works: nothing is filled
                // in, and 4.6a asks for the pastor's email.
            }
        }

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
                'pastor_name', ...(needsPastorEmail.value ? ['pastor_email'] : []),
                'attendance_duration', 'ministry_involvement',
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
            loadPastors()
        })
        onBeforeUnmount(() => window.removeEventListener('scroll', onScroll))

        return {
            form, errors, generalError, sending, submitted, successHeading, today,
            sections, levels, churches, learningDifficulties, spiritualQuestions,
            sectionDone, current, goToSection, q, invalid, describedBy,
            pastorFilled, pastorStatus, needsPastorEmail,
            formatDate, pickTranscript, submit,
            returningFormUrl: 'https://forms.gle/9nEDxXdJgGbtwr5m9',
        }
    },
})
</script>

<style scoped src="./abc-form-document.css"></style>

<style scoped>
/* Form ABC-1 only. The design both college forms share is in
   abc-form-document.css. */

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

/* Honeypot */
.hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

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

/* Phone */
@media (max-width: 760px) {
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
}
</style>
