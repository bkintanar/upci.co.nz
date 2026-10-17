/*
 * Classes for Markdown typed in the admin, used wherever it is shown in the
 * "Quiet frame" design: a department's description and every announcement
 * (Department.vue, AnnouncementList.vue). Moved out of Department.vue on
 * 2026-10-17 so the two stay one style.
 */
// Markdown from the admin: there is no typography plugin, so
// paragraphs, links and lists get their spacing and style here.
// (`prose` stays on the container for the site's embed rules in
// app.css.)
// [overflow-wrap:anywhere]: a pasted link (the Men's description ends
// with a long Facebook URL) wraps instead of widening a phone screen.
export const MARKDOWN_CLASSES = '[overflow-wrap:anywhere] space-y-2.5 [&_a]:text-brand-green-700 [&_a]:underline [&_a]:underline-offset-[3px] [&_strong]:font-semibold [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_blockquote]:italic'
