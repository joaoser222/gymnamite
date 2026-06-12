import { TextInput, TextInputProps } from '@mantine/core';

type InputProps = Omit<TextInputProps, 'ref'>;

function createInput(label: string, placeholder: string) {
    return function MaskedField(props: InputProps) {
        return (
            <TextInput
                label={label}
                placeholder={placeholder}
                {...props}
            />
        );
    };
}

export const CpfInput = createInput('CPF', '___.___.___-__');
export const PhoneInput = createInput('Telefone', '(__) _____-____');
export const CepInput = createInput('CEP', '_____-___');
export const CnpjInput = createInput('CNPJ', '__.___.___/____-__');
