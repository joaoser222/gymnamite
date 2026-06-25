import type { Directive } from 'vue';

type TextCaseMode = 'upper' | 'lower';

const transforms: Record<TextCaseMode, (v: string) => string> = {
    upper: (v) => v.toUpperCase(),
    lower: (v) => v.toLowerCase(),
};

export const vTextCase: Directive<HTMLInputElement, TextCaseMode> = {
    mounted(el, binding) {
        const input = el.tagName === 'INPUT' ? el : el.querySelector('input');

        if (!input) return;

        const getTransform = () => transforms[binding.value] ?? transforms.upper;

        input.addEventListener('input', () => {
            const start = input.selectionStart;
            const end = input.selectionEnd;

            input.value = getTransform()(input.value);
            input.dispatchEvent(new Event('input', { bubbles: true }));

            input.setSelectionRange(start, end);
        });
    },
    updated(el, binding) {
        if (binding.value !== binding.oldValue) {
            const input = el.tagName === 'INPUT' ? el : el.querySelector('input');

            if (input) {
                const transform = transforms[binding.value] ?? transforms.upper;
                input.value = transform(input.value);
            }
        }
    },
};
