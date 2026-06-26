import test from 'node:test';
import assert from 'node:assert/strict';
import {
    formatCurrency,
    formatDate,
    formatDateTime,
    onlyAlphanumeric,
    onlyDigits,
} from '../../plugins/formatters.ts';

test('onlyDigits removes non-digit characters', () => {
    assert.equal(onlyDigits('ABC 12.3-4'), '1234');
});

test('onlyAlphanumeric removes non-alphanumeric characters', () => {
    assert.equal(onlyAlphanumeric('ABC 12.3-4'), 'ABC1234');
});

test('formatDate formats dates in pt-BR', () => {
    assert.equal(formatDate(new Date(2024, 0, 15)), '15/01/2024');
});

test('formatDateTime formats datetimes in pt-BR', () => {
    assert.match(formatDateTime(new Date(2024, 0, 15, 13, 45)), /15\/01\/2024.*13:45/);
});

test('formatCurrency formats BRL values', () => {
    assert.equal(formatCurrency(1234.5), 'R$\u00a01.234,50');
});

test('formatters return fallback for invalid values', () => {
    assert.equal(formatDate(null), '-');
    assert.equal(formatDateTime(undefined), '-');
    assert.equal(formatCurrency('abc'), '-');
});
