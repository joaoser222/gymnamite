import { onlyAlphanumeric, onlyDigits } from './formatters.ts';

export type MaskToken = {
    pattern: RegExp;
    transform?: (value: string) => string;
};

export type UnmaskMode = 'mask' | 'digits' | 'alphanumeric' | 'none';

export const maskTokens: Record<string, MaskToken> = {
    '#': { pattern: /\d/ },
    A: {
        pattern: /[a-zA-Z]/,
        transform: (value: string) => value.toUpperCase(),
    },
    X: { pattern: /[a-zA-Z0-9]/ },
};

export const masks = {
    cep: '#####-###',
    cnpj: '##.###.###/####-##',
    cpf: '###.###.###-##',
    landlinePhone: '(##) ####-####',
    phone: '(##) #####-####',
} as const;

export function phoneMask(value?: string | number | null): string {
    return onlyDigits(value).length > 10 ? masks.phone : masks.landlinePhone;
}

export function documentMask(value?: string | number | null): string {
    return onlyDigits(value).length > 11 ? masks.cnpj : masks.cpf;
}

export function unmaskValue(
    value?: string | number | null,
    mask = '',
    mode: UnmaskMode = 'mask',
    limitToMask = true,
): string {
    const stringValue = String(value ?? '');
    const maskLength = countMaskTokens(mask);

    if (mode === 'none') {
        return stringValue;
    }

    if (mode === 'digits') {
        return limitValue(onlyDigits(stringValue), maskLength, limitToMask);
    }

    if (mode === 'alphanumeric') {
        return limitValue(onlyAlphanumeric(stringValue), maskLength, limitToMask);
    }

    return limitValue(unmaskByMask(stringValue, mask), maskLength, limitToMask);
}

export function applyMask(
    value?: string | number | null,
    mask = '',
    mode: UnmaskMode = 'mask',
): string {
    if (!mask) {
        return String(value ?? '');
    }

    const rawValue = unmaskValue(value, mask, mode);
    let rawIndex = 0;
    let formattedValue = '';

    for (const maskChar of mask) {
        const token = maskTokens[maskChar];

        if (!token) {
            if (rawIndex < rawValue.length) {
                formattedValue += maskChar;
            }

            continue;
        }

        const rawChar = rawValue[rawIndex];

        if (!rawChar) {
            break;
        }

        if (token.pattern.test(rawChar)) {
            formattedValue += token.transform?.(rawChar) ?? rawChar;
            rawIndex++;
        }
    }

    return formattedValue;
}

export const formatMasks = {
    cep: (value?: string | number | null): string =>
        applyMask(value, masks.cep),
    cnpj: (value?: string | number | null): string =>
        applyMask(value, masks.cnpj),
    cpf: (value?: string | number | null): string =>
        applyMask(value, masks.cpf),
    document: (value?: string | number | null): string =>
        applyMask(value, documentMask(value)),
    phone: (value?: string | number | null): string =>
        applyMask(value, phoneMask(value)),
};

function unmaskByMask(value: string, mask: string): string {
    if (!mask) {
        return value;
    }

    const availableTokens = new Set(
        [...mask].filter((char) => maskTokens[char] !== undefined),
    );

    return [...value]
        .filter((char) =>
            [...availableTokens].some((token) =>
                maskTokens[token].pattern.test(char),
            ),
        )
        .join('');
}

function countMaskTokens(mask: string): number {
    return [...mask].filter((char) => maskTokens[char] !== undefined).length;
}

function limitValue(
    value: string,
    maskLength: number,
    limitToMask: boolean,
): string {
    if (!limitToMask || maskLength === 0) {
        return value;
    }

    return value.slice(0, maskLength);
}
