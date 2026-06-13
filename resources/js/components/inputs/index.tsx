import type { TextInputProps } from '@mantine/core';
import { TextInput } from '@mantine/core';
import { maskedInputProps, masks } from '@/utils/mask';

type InputProps = Omit<TextInputProps, 'ref'>;

function createInput(
    label: string,
    placeholder: string,
    formatter?: (value: unknown) => string,
) {
    return function MaskedField(props: InputProps) {
        return (
            <TextInput
                label={label}
                placeholder={placeholder}
                {...(formatter ? maskedInputProps(props, formatter) : props)}
            />
        );
    };
}

export const CpfInput = createInput('CPF', '___.___.___-__', masks.cpf);
export const PhoneInput = createInput('Telefone', '(__) _____-____', masks.phone);
export const CepInput = createInput('CEP', '_____-___', masks.cep);
export const CnpjInput = createInput('CNPJ', '__.___.___/____-__', masks.cnpj);
