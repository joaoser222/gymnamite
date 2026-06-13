import type { ChangeEvent, ChangeEventHandler } from 'react';

type InputPropsWithValue = {
    value?: string | number | readonly string[];
    onChange?: ChangeEventHandler<HTMLInputElement> | ((value: string) => void);
};

export function onlyDigits(value: unknown): string {
    return String(value ?? '').replace(/\D/g, '');
}

export function applyPattern(value: unknown, pattern: string): string {
    const digits = onlyDigits(value);
    let digitIndex = 0;
    let result = '';

    for (const char of pattern) {
        if (digitIndex >= digits.length) {
            break;
        }

        if (char === '#') {
            result += digits[digitIndex];
            digitIndex += 1;
            continue;
        }

        result += char;
    }

    return result;
}

export const masks = {
    cpf: (value: unknown): string => applyPattern(value, '###.###.###-##'),
    cnpj: (value: unknown): string => applyPattern(value, '##.###.###/####-##'),
    cep: (value: unknown): string => applyPattern(value, '#####-###'),
    phone: (value: unknown): string => {
        const digits = onlyDigits(value).slice(0, 11);

        return digits.length <= 10
            ? applyPattern(digits, '(##) ####-####')
            : applyPattern(digits, '(##) #####-####');
    },
};

export const unmask = {
    digits: onlyDigits,
};

export function maskedInputProps<T extends InputPropsWithValue>(
    props: T,
    formatter: (value: unknown) => string,
    parser: (value: unknown) => string = onlyDigits,
): T {
    return {
        ...props,
        value: formatter(props.value),
        onChange: (event: ChangeEvent<HTMLInputElement>) => {
            const rawValue = parser(event.currentTarget.value);
            const onChange = props.onChange as
                | ((value: string) => void)
                | undefined;

            onChange?.(rawValue);
        },
    } as T;
}
