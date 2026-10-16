import { h } from 'vue'

/*
 * Small pieces shared by the college's two forms, Form ABC-1 (the new-student
 * enrolment) and Form ABC-2 (the pastor's reference). Their styles are in
 * abc-form-document.css. Moved out of AbcNewStudentEnrolment.vue unchanged on
 * 2026-10-17.
 */

// "Required" said in words as well as the asterisk, for screen readers.
export const Req = () => [
    h('span', { class: 'req', 'aria-hidden': 'true' }, '*'),
    h('span', { class: 'vh' }, ' (required)'),
]

// The server's first message for a field, id'd so the input can point at it.
export const FieldError = (props) => props.errors?.[props.field]?.length
    ? h('p', { class: 'err-msg', id: `${props.field}-error` }, [h('span', { class: 'vh' }, 'Error: '), props.errors[props.field][0]])
    : null
FieldError.props = ['errors', 'field']

// Yes/No radios. The first radio carries the field name as its id, so the
// error summary's #field link lands on the group.
export const YesNo = (props, { emit }) => h('div', { class: 'opts' }, ['yes', 'no'].map((value) =>
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

// Local date, not toISOString(): that is UTC, which is yesterday in New
// Zealand every morning.
export const localDate = (d = new Date()) => {
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

// "17 October 2026" from a YYYY-MM-DD string, read as a local date.
export const formatDate = (value) => {
    if (!value) return '—'
    const [y, m, d] = value.split('-').map(Number)
    return new Date(y, m - 1, d).toLocaleDateString('en-NZ', { day: 'numeric', month: 'long', year: 'numeric' })
}
