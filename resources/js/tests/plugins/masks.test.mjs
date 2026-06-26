import test from 'node:test';
import assert from 'node:assert/strict';
import {
    applyMask,
    documentMask,
    formatMasks,
    masks,
    unmaskValue,
} from '../../plugins/masks.ts';

test('unmaskValue limits digit input to the number of mask tokens', () => {
    assert.equal(unmaskValue('1234567890123', masks.cpf, 'digits'), '12345678901');
});

test('unmaskValue limits alphanumeric input to the number of mask tokens', () => {
    assert.equal(unmaskValue('ABC123XYZ9', 'AAA-###', 'alphanumeric'), 'ABC123');
});

test('applyMask still formats only the supported amount of characters', () => {
    assert.equal(applyMask('1234567890123', masks.cpf, 'digits'), '123.456.789-01');
});

test('documentMask keeps CPF mask up to 11 digits and switches to CNPJ afterwards', () => {
    assert.equal(documentMask('12345678901'), masks.cpf);
    assert.equal(documentMask('123456789012'), masks.cnpj);
});

test('formatMasks.document formats CPF and CNPJ dynamically', () => {
    assert.equal(formatMasks.document('12345678901'), '123.456.789-01');
    assert.equal(formatMasks.document('12345678901234'), '12.345.678/9012-34');
});

test('unmaskValue can preserve extra digits so a dynamic mask can switch to CNPJ', () => {
    assert.equal(
        unmaskValue('123.456.789-012', masks.cpf, 'mask', false),
        '123456789012',
    );
});
