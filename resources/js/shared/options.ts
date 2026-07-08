export type OptionValue = string | number | boolean | null;

export type Option<TValue = OptionValue> = {
    title: string;
    value: TValue;
    color?: string;
    icon?: string;
    disabled?: boolean;
};

export type LabeledOption<TValue = OptionValue> = {
    label: string;
    value: TValue;
    color?: string;
};

export const toOptions = <T extends OptionValue>(
    items: LabeledOption<T>[] | undefined,
): Option<T>[] =>
    items?.map(({ label, value, color }) => ({ title: label, value, color })) ?? [];

export const useSharedOptions = <
    T extends Record<string, LabeledOption[] | undefined>,
>(
    source: T,
): { [K in keyof T]: Option[] } => {
    const result = {} as { [K in keyof T]: Option[] };

    for (const key in source) {
        result[key] = toOptions(source[key]);
    }

    return result;
};

export const findLabel = <T extends OptionValue>(
    items: (Option<T> | LabeledOption<T>)[] | undefined,
    value: T,
): string | undefined => {
    const normalizedValue = normalizeOptionValue(value);
    const item = items?.find((i) => normalizeOptionValue(i.value) === normalizedValue);
    return item ? ('title' in item ? item.title : item.label) : undefined;
};

export const findOption = <T extends OptionValue>(
    items: (Option<T> | LabeledOption<T>)[] | undefined,
    value: T,
): (Option<T> | LabeledOption<T>) | undefined => {
    const normalizedValue = normalizeOptionValue(value);
    return items?.find((item) => normalizeOptionValue(item.value) === normalizedValue);
};

function normalizeOptionValue(value: unknown): unknown {
    if (
        typeof value === 'object' &&
        value !== null &&
        'value' in value
    ) {
        return (value as { value: unknown }).value;
    }

    return value;
}
