import { describe, it, expect } from 'vitest';
import { isEmpty, isSet, isTrue, isFalse, isObject, isArray, isString } from '@syncengine/utils/conditions';

describe('isEmpty', () => {
	it('returns true for null/undefined', () => {
		expect(isEmpty(null)).toBe(true);
		expect(isEmpty(undefined)).toBe(true);
	});

	it('returns true for empty string/number/array/object', () => {
		expect(isEmpty('')).toBe(true);
		expect(isEmpty(0)).toBe(true);
		expect(isEmpty([])).toBe(true);
		expect(isEmpty({})).toBe(true);
	});

	it('returns false for non-empty values', () => {
		expect(isEmpty('hello')).toBe(false);
		expect(isEmpty(1)).toBe(false);
		expect(isEmpty([1])).toBe(false);
		expect(isEmpty({ a: 1 })).toBe(false);
	});
});

describe('isSet', () => {
	it('returns true for any value except null/undefined', () => {
		expect(isSet(0)).toBe(true);
		expect(isSet(false)).toBe(true);
		expect(isSet('')).toBe(true);
		expect(isSet(null)).toBe(false);
		expect(isSet(undefined)).toBe(false);
	});
});

describe('isTrue/isFalse', () => {
	it('handles string booleans', () => {
		expect(isTrue('true')).toBe(true);
		expect(isTrue('TRUE')).toBe(true);
		expect(isFalse('false')).toBe(true);
		expect(isFalse('FALSE')).toBe(true);
	});

	it('handles loose booleans', () => {
		expect(isTrue(true)).toBe(true);
		expect(isFalse(false)).toBe(true);
		expect(isTrue(0)).toBe(false);
		expect(isFalse(0)).toBe(true);
	});
});

describe('isObject/isArray/isString', () => {
	it('identifies object types correctly', () => {
		expect(isObject({})).toBe(true);
		expect(isObject([])).toBe(false);
		expect(isObject(null)).toBe(false);
		expect(isObject('')).toBe(false);
	});

	it('identifies arrays correctly', () => {
		expect(isArray([])).toBe(true);
		expect(isArray([1])).toBe(true);
		expect(isArray({})).toBe(false);
		expect(isArray(null)).toBe(false);
	});

	it('identifies strings correctly', () => {
		expect(isString('')).toBe(true);
		expect(isString('hello')).toBe(true);
		expect(isString(0)).toBe(false);
		expect(isString(null)).toBe(false);
	});
});
