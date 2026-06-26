import test from 'node:test';
import assert from 'node:assert/strict';
import { cep, cnpj, cpf, cpfCnpj, exactLength, phone } from '../../plugins/validators.ts';

test('cpf ignores punctuation when validating', () => {
    assert.equal(cpf('529.982.247-25'), true);
});

test('cnpj ignores punctuation when validating', () => {
    assert.equal(cnpj('12.345.678/0001-56'), true);
});

test('cpfCnpj routes CPF and CNPJ values correctly', () => {
    assert.equal(cpfCnpj('529.982.247-25'), true);
    assert.equal(cpfCnpj('12.345.678/0001-56'), true);
});

test('phone ignores punctuation when validating', () => {
    assert.equal(phone('(11) 99888-7777'), true);
});

test('cep ignores punctuation when validating', () => {
    assert.equal(cep('01001-000'), true);
});

test('exactLength requires the exact number of characters', () => {
    const validator = exactLength(11);

    assert.equal(validator('12345678901'), true);
    assert.equal(validator('1234567890'), 'Deve ter 11 caracteres');
    assert.equal(validator('123456789012'), 'Deve ter 11 caracteres');
});
