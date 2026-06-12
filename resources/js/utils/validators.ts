function extractValue(v: unknown): string {
  if (typeof v === 'string') return v;
  if (typeof v === 'object' && v !== null && 'target' in v) {
    return (v as any).target?.value ?? '';
  }
  return String(v ?? '');
}

type Validator = (v: unknown, label?: string) => string | null;

export const validators = {
  required: () =>
    (v: unknown, label = 'Campo') =>
      !extractValue(v).trim() ? `${label} é obrigatório` : null,
  email: () =>
    (v: unknown, label = 'Campo') =>
      !/\S+@\S+\.\S+/.test(extractValue(v)) ? 'E-mail inválido' : null,
  minLength: (min: number) =>
    (v: unknown, label = 'Campo') =>
      extractValue(v).length < min ? `${label} deve ter no mínimo ${min} caracteres` : null,
  maxLength: (max: number) =>
    (v: unknown, label = 'Campo') =>
      extractValue(v).length > max ? `${label} deve ter no máximo ${max} caracteres` : null,
  compose: (label: string, ...fns: Validator[]) =>
    (v: unknown) => fns.reduce<string | null>((err, fn) => err ?? fn(v, label), null),
};