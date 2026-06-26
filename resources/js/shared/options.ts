export type OptionValue = string | number | boolean | null;

export type Option<TValue = OptionValue> = {
    title: string;
    value: TValue;
    icon?: string;
    disabled?: boolean;
};

export type LabeledOption<TValue = OptionValue> = {
    label: string;
    value: TValue;
};

export const toOptions = <T extends OptionValue>(
    items: LabeledOption<T>[] | undefined,
): Option<T>[] =>
    items?.map(({ label, value }) => ({ title: label, value })) ?? [];

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
    const item = items?.find((i) => i.value === value);
    return item ? ('title' in item ? item.title : item.label) : undefined;
};
