# Design spec — ABC first-time student enrolment page

Shared input for all three directions. Each direction reads ONLY this file (plus `brand-spec.md`)
and must not look at the others.

## What it is

A single page on the UPCI New Zealand website — `/apostolic-bible-college/enrollment/new-student` —
where someone enrolling in the **Apostolic Bible College (ABC)** for the first time fills in their
application. It replaces a Google Form. The college is the national Bible school of the United
Pentecostal Church International in New Zealand; it is fully online. The page lives inside the
existing site (site navbar above, footer below — the demo should show a thin, honest site header
strip with the logo so the page is seen in context, but the design work is the page body).

## Who uses it and how

- **Applicants**: adults (typically 18–60), many on phones, some for whom English is a second
  language, a few with declared learning difficulties (hearing, vision, writing, English). They are
  committed church members, often referred by their pastor. Filling this in is a meaningful step for
  them — a calling, not a checkout. It takes ~10–15 minutes.
- **Viewing distance**: 30 cm phone (375px wide) is the primary case; 1 m laptop second.
- The college staff read submissions in the admin — not part of this design.

## Core message and tone

"You are welcome here, and this is a serious, orderly step." Warm, plain-spoken, institutional but
not corporate (per brand-spec §5). Trustworthy and organised first, contemporary second. Not a
startup landing page, not a tax form either.

## Content — use EXACTLY this content (real copy from the Google Form; no Lorem, no invented text)

**Page title:** New Student Enrolment — Apostolic Bible College

**Intro:** "Praise the Lord! We warmly welcome you to the Apostolic Bible School (ABC) of UPCI-NZ. We
are blessed to see your interest in enriching and equipping yourself for the furtherance of God's
kingdom. This form is for students that are enrolling to UPCI-NZ Apostolic Bible School for the first
time. Kindly fill them completely to avoid any delays in your application. If you have been a student
of Apostolic Bible School (ABC) before, kindly use the Enrolment Form for Returning Students." (the
last phrase is a link)

**Progression of Study** (was an image; must be real HTML): Certificate Level (Year 1) → Intermediate
Diploma (Year 1) → Intermediate Diploma (Year 2) → Full Diploma (Year 1) → Full Diploma (Year 2).
Caption: "All new students who enrol with UPCI-NZ ABC begin their studies at the Certificate Level, and
progress to higher levels accordingly after completing the required units. If you are a new student to
ABC but have completed a level of study with other UPCI Bible School, kindly indicate it at Section 2."
Source image for reference only: `progression-of-study-source.png` (do not embed it — rebuild it).

**Section 1. Personal information** — Full name* · Date of birth* (date) · Gender* (radio: Male,
Female) · Citizenship* · Address* (textarea) · Home phone* · Mobile phone* · Email* · Are you currently
residing in New Zealand?* (radio Yes/No) → if No: "If residing in a country other than New Zealand,
please specify where"* · Why do you want to attend the Apostolic Bible College of NZ?* (textarea)

**Section 2. Previous Bible school attendance** — Have you ever attended a UPCI Bible school before?*
(Yes/No) → if Yes: help text "Please attach the transcript of your previous study. If you have
queries, don't hesitate to reach out to us at apostolicbiblecollege@upci.co.nz" + optional file input
"Transcript (PDF, JPG or PNG, up to 10 MB)".

**Section 3. Learning challenges** — Do you have any learning difficulties?* (Yes/No) → if Yes: "If
yes, please indicate which from the list below:"* (radio: Hearing, Vision, Writing, Using English in
learning, Other) → if Other: text "Please describe"*.

**Section 4. Spiritual information** — Do you desire to become a licensed minister under UPCI-NZ?* ·
Have you read and understood the Articles of Faith?* · Have you been baptised in the name of Jesus
Christ?* · Have you received the Holy Ghost by the evidence of tongues?* (all Yes/No) · What is the
name of the church you currently attend?* (radio list, 13 + Other): Apostolic Life Church (Tauranga);
Apostolics of Christchurch (Christchurch); Church Triumphant Wellington (Wellington); Daystar
Fellowship (Whangarei); El Shaddai- Hindi Speaking Fellowship (Auckland); Grace Fellowship (Hamilton);
New Zealand Family in Christ (Auckland); Pentecostals of Rangiora (Rangiora); Solid Foundation Church
(Hastings); Southside Pentecostal Fellowship (Auckland); Storehouse Chapel (Hamilton); Walk by Faith
(Auckland); House of Mercy (Tokoroa); Other → text "Church name"* · Who is your church pastor?* · How
long have you attended?* · Describe any area of ministry you are currently involved in.* (textarea)

**Section 5. Financial information & agreement** — "Students are encouraged to settle their fees as
soon as possible. Should you wish to discuss an alternative payment plan, please contact the Bible
college administrator. All students will receive an invoice with relevant fee and payment information
before course begins." + a **Schedule of fees** slot → this is an HONEST PLACEHOLDER: a dashed box
labelled "Schedule of fees — to be supplied by the college" (the real data is not available yet; do NOT
invent fee amounts). Then "I hereby certify/agree that:" with exactly four terms:
1. the information provided on this application is complete and accurate to the best of my knowledge
   and the Apostolic Bible College of NZ is authorized to make whatever inquiries are necessary to
   certify the accuracy of my records;
2. the registration fee of $50.00 is non-refundable;
3. the course material provided to me is the property of the Apostolic Bible College of NZ until such
   time that I pay the total fees for the calendar year, and that if I withdraw from the programme
   prior to the full payment of fees, that the course material must be returned to the Bible School;
4. I will adhere to the student requirements as outlined in the Student Handbook.
Then: If you agree, please print your full name* · If you agree, please select today's date* (date,
prefilled with today).

**Privacy notice** (just above submit): "The information you provide is collected by UPCI New
Zealand's Apostolic Bible College to process your enrolment. It is seen only by national UPCI-NZ
administrators, and you can ask to see or correct it by emailing apostolicbiblecollege@upci.co.nz."

**Submit**: "Submit enrolment". Success state (show it as a second, smaller frame or a toggle in the
demo): "Thank you — your enrolment has been received. The Apostolic Bible College will be in touch by
email." + link back to the college page.

**Error state** (show it in the demo, statically or via a toggle): the existing error-summary pattern —
a box at the top of the form titled "There is a problem", a list of links to each failing field, the
failing fields with a 2px error-red border and a red message under them. Show at least 3 errors
(e.g. Date of birth, Gender, Church).

## Output format (identical for all three directions)

- ONE self-contained HTML file, plain HTML + CSS (+ a few lines of vanilla JS only for toggles:
  conditional reveal on the radios, error-state toggle, success-state toggle, step navigation if the
  direction has steps). No frameworks, no CDN scripts. Google Fonts `<link>` for Figtree is allowed.
- Must render correctly at **1440 × 900 (desktop)** and at **375 px wide (mobile) with no horizontal
  scroll**.
- Logo: embed `logo.b64` as a data URI (`data:image/png;base64,...`) in the site header strip.

## Constraints (hard)

- **Colour**: ONLY the brand tokens (brand-spec §2): ink `oklch(0.18 0.01 150)`, paper `oklch(0.98 0.005
  95)`, green-100 `oklch(0.93 0.03 143)`, green-700 `oklch(0.47 0.09 143)` (THE brand colour), green-900
  `oklch(0.34 0.07 143)`, clay-600 `oklch(0.62 0.12 55)` (warm accent, sparingly), grey-200/400/600
  `oklch(0.90/0.72/0.55 …)`, and error `oklch(0.52 0.17 27)` **for error states only**. White is allowed
  for input fields. No other hues.
- **Type**: Figtree (the site's face). Body ≥ 16px on inputs (prevents iOS zoom), labels ≥ 14px, notes ≥
  12px; contrast ≥ 4.5:1.
- **Accessibility**: every radio group is a `<fieldset>` with a `<legend>`; every input has a `<label
  for>`; input `id` equals its field name (the error summary links to `#field`); required fields
  marked in text, not colour alone; visible focus ring (green-700); radios/checkboxes have ≥ 44px
  touch targets on mobile.
- **Behaviour contract**: whatever the layout, it submits as ONE request at the end. A stepper is
  allowed only as client-side navigation over the same single form.
- **Forbidden** (brand-spec §4 + anti-slop): purple/blue gradients, emoji, rounded-card-with-left-colour
  -border, SVG-drawn people, invented stats, stock photography, icon-on-every-heading. `upca.org.au` is
  the sister site and the basis of the brand family — do NOT replicate its look.

## Visual motif hypothesis (from the content, not a style label)

The content has its own structure worth expressing: (a) the **Progression of Study** — a five-rung
ladder from Certificate to Full Diploma — is the applicant's future path, and the form itself is the
first rung; (b) the **agreement** ends in a printed name and a date — a signature line, the language of
a formal document; (c) the form is **five numbered sections**, exactly like the paper original. A good
direction lets at least one of these shape the form, rather than decorating a generic form.

## What success looks like

An applicant on a phone finishes without confusion, never wonders how much is left, can recover from
an error in one tap, and feels they have applied to a real college — not filled in a survey.
