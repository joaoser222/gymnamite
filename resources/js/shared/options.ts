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
