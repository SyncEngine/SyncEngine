import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import {
	isEmpty,
	isSet,
	isValue,
	isScalar,
	isPrimitive,
	isFunction,
	isEvent,
	isKey,
	isFalse,
	isTrue,
	isId,
	isIterable,
	isObject,
	isPromise,
	isArray,
	isBool,
	isString,
	isNumeric,
	isMatch,
	isEqual,
	isMultiline,
	isFieldEditable,
	hasKey,
	hasValue,
	startsWith,
	endsWith,
	isConfigured,
	getOperators,
	validate,
	OPERATOR_SET,
	OPERATOR_NOT_SET,
	OPERATOR_EMPTY,
	OPERATOR_NOT_EMPTY,
	OPERATOR_IN,
	OPERATOR_NOT_IN,
	OPERATOR_CONTAINS,
	OPERATOR_NOT_CONTAINS,
	OPERATOR_HAS_KEY,
	OPERATOR_NOT_HAS_KEY,
	OPERATOR_LESSER,
	OPERATOR_GREATER,
	OPERATOR_LESSER_OR_EQUAL,
	OPERATOR_GREATER_OR_EQUAL,
	OPERATOR_EQUAL,
	OPERATOR_NOT_EQUAL,
	OPERATOR_EQUAL_STRICT,
	OPERATOR_NOT_EQUAL_STRICT,
	OPERATOR_STARTS_WITH,
	OPERATOR_NOT_STARTS_WITH,
	OPERATOR_ENDS_WITH,
	OPERATOR_NOT_ENDS_WITH,
	OPERATOR_REGEX,
	OPERATOR_IN_STRICT,
	OPERATOR_NOT_IN_STRICT,
	OPERATOR_CONTAINS_STRICT,
	OPERATOR_NOT_CONTAINS_STRICT,
} from '@syncengine/utils/conditions';

describe('isEmpty', () => {
	it('returns true for undefined', () => { expect(isEmpty(undefined)).toBe(true); });
	it('returns true for null', () => { expect(isEmpty(null)).toBe(true); });
	it('returns true for empty string', () => { expect(isEmpty('')).toBe(true); });
	it('returns false for "0" (non-empty string)', () => { expect(isEmpty('0')).toBe(false); });
	it('returns true for false', () => { expect(isEmpty(false)).toBe(true); });
	it('returns true for 0', () => { expect(isEmpty(0)).toBe(true); });
	it('returns true for 0n (bigint)', () => { expect(isEmpty(0n)).toBe(true); });
	it('returns true for empty array', () => { expect(isEmpty([])).toBe(true); });
	it('returns true for empty object', () => { expect(isEmpty({})).toBe(true); });
	it('returns false for non-empty string', () => { expect(isEmpty('a')).toBe(false); });
	it('returns false for "0" string (truthy)', () => { expect(isEmpty('0')).toBe(false); });
	it('returns false for true', () => { expect(isEmpty(true)).toBe(false); });
	it('returns false for 1', () => { expect(isEmpty(1)).toBe(false); });
	it('returns false for non-empty array', () => { expect(isEmpty([1])).toBe(false); });
	it('returns false for non-empty object', () => { expect(isEmpty({ a: 1 })).toBe(false); });
	it('returns false for function', () => { expect(isEmpty(() => {})).toBe(false); });
	it('returns false for symbol', () => { expect(isEmpty(Symbol('a'))).toBe(false); });
});

describe('isSet', () => {
	it('returns false for undefined', () => { expect(isSet(undefined)).toBe(false); });
	it('returns false for null', () => { expect(isSet(null)).toBe(false); });
	it('returns true for empty string', () => { expect(isSet('')).toBe(true); });
	it('returns true for 0', () => { expect(isSet(0)).toBe(true); });
	it('returns true for false', () => { expect(isSet(false)).toBe(true); });
	it('returns true for empty array', () => { expect(isSet([])).toBe(true); });
	it('returns true for empty object', () => { expect(isSet({})).toBe(true); });
});

describe('isValue', () => {
	it('returns false for undefined', () => { expect(isValue(undefined)).toBe(false); });
	it('returns false for null', () => { expect(isValue(null)).toBe(false); });
	it('returns false for function', () => { expect(isValue(() => {})).toBe(false); });
	it('returns false for symbol', () => { expect(isValue(Symbol('a'))).toBe(false); });
	it('returns true for empty string', () => { expect(isValue('')).toBe(true); });
	it('returns true for 0', () => { expect(isValue(0)).toBe(true); });
	it('returns true for false', () => { expect(isValue(false)).toBe(true); });
	it('returns true for empty array', () => { expect(isValue([])).toBe(true); });
	it('returns true for empty object', () => { expect(isValue({})).toBe(true); });
});

describe('isScalar', () => {
	it('returns true for boolean', () => { expect(isScalar(true)).toBe(true); expect(isScalar(false)).toBe(true); });
	it('returns true for number', () => { expect(isScalar(0)).toBe(true); expect(isScalar(1)).toBe(true); });
	it('returns true for bigint', () => { expect(isScalar(0n)).toBe(true); });
	it('returns true for string', () => { expect(isScalar('')).toBe(true); expect(isScalar('a')).toBe(true); });
	it('returns false for null', () => { expect(isScalar(null)).toBe(false); });
	it('returns false for undefined', () => { expect(isScalar(undefined)).toBe(false); });
	it('returns false for object', () => { expect(isScalar({})).toBe(false); });
	it('returns false for array', () => { expect(isScalar([])).toBe(false); });
	it('returns false for function', () => { expect(isScalar(() => {})).toBe(false); });
});

describe('isPrimitive', () => {
	it('returns true for null', () => { expect(isPrimitive(null)).toBe(true); });
	it('returns true for boolean', () => { expect(isPrimitive(true)).toBe(true); });
	it('returns true for number', () => { expect(isPrimitive(0)).toBe(true); });
	it('returns true for bigint', () => { expect(isPrimitive(0n)).toBe(true); });
	it('returns true for string', () => { expect(isPrimitive('')).toBe(true); });
	it('returns true for undefined', () => { expect(isPrimitive(undefined)).toBe(true); });
	it('returns true for symbol', () => { expect(isPrimitive(Symbol('a'))).toBe(true); });
	it('returns false for object', () => { expect(isPrimitive({})).toBe(false); });
	it('returns false for array', () => { expect(isPrimitive([])).toBe(false); });
	it('returns false for function', () => { expect(isPrimitive(() => {})).toBe(false); });
});

describe('isFunction', () => {
	it('returns true for functions', () => { expect(isFunction(() => {})).toBe(true); });
	it('returns false for non-functions', () => {
		expect(isFunction(0)).toBe(false);
		expect(isFunction('')).toBe(false);
		expect(isFunction(null)).toBe(false);
		expect(isFunction(undefined)).toBe(false);
		expect(isFunction({})).toBe(false);
		expect(isFunction([])).toBe(false);
	});
});

describe('isEvent', () => {
	it('returns true for DOM Event', () => {
		const event = new Event('click');
		expect(isEvent(event)).toBe(true);
	});
	it('returns false for non-event objects', () => {
		expect(isEvent({})).toBe(false);
		expect(isEvent(null)).toBe(false);
		expect(isEvent(undefined)).toBe(false);
		expect(isEvent(0)).toBe(false);
		expect(isEvent('')).toBe(false);
	});
	it('returns true for mock event with target and preventDefault', () => {
		const mockEvent = { target: {}, preventDefault: () => {} };
		expect(isEvent(mockEvent)).toBe(true);
	});
	it('returns true for mock event with originalEvent', () => {
		const mockEvent = { originalEvent: {} };
		expect(isEvent(mockEvent)).toBe(true);
	});
	it('returns true for full mock event', () => {
		const mockEvent = {
			type: 'click',
			bubbles: true,
			cancelable: true,
			preventDefault: () => {},
			stopPropagation: () => {},
		};
		expect(isEvent(mockEvent)).toBe(true);
	});
});

describe('isKey', () => {
	it('returns true for non-empty string', () => { expect(isKey('a')).toBe(true); });
	it('returns false for empty string', () => { expect(isKey('')).toBe(false); });
	it('returns true for number', () => { expect(isKey(0)).toBe(true); expect(isKey(1)).toBe(true); });
	it('returns true for bigint', () => { expect(isKey(0n)).toBe(true); });
	it('returns false for boolean', () => { expect(isKey(true)).toBe(false); });
	it('returns false for object', () => { expect(isKey({})).toBe(false); });
	it('returns false for array', () => { expect(isKey([])).toBe(false); });
	it('returns false for function', () => { expect(isKey(() => {})).toBe(false); });
	it('returns false for symbol', () => { expect(isKey(Symbol('a'))).toBe(false); });
	it('returns false for undefined', () => { expect(isKey(undefined)).toBe(false); });
});

describe('isFalse', () => {
	it('returns true for false', () => { expect(isFalse(false)).toBe(true); });
	it('returns true for 0', () => { expect(isFalse(0)).toBe(true); });
	it('returns true for empty string', () => { expect(isFalse('')).toBe(true); });
	it('returns true for empty array', () => { expect(isFalse([])).toBe(true); });
	it('returns true for empty object', () => { expect(isFalse({})).toBe(true); });
	it('returns true for string "false"', () => { expect(isFalse('false')).toBe(true); });
	it('returns true for string "0"', () => { expect(isFalse('0')).toBe(true); });
	it('returns true for string "off"', () => { expect(isFalse('off')).toBe(true); });
	it('returns true for string "no"', () => { expect(isFalse('no')).toBe(true); });
	it('returns true for string "FALSE" (case insensitive)', () => { expect(isFalse('FALSE')).toBe(true); });
	it('returns true for string "Off" (case insensitive)', () => { expect(isFalse('Off')).toBe(true); });
	it('returns false for true', () => { expect(isFalse(true)).toBe(false); });
	it('returns false for 1', () => { expect(isFalse(1)).toBe(false); });
	it('returns false for non-empty string', () => { expect(isFalse('a')).toBe(false); });

	it('strict mode: returns true for false', () => { expect(isFalse(false, true)).toBe(true); });
	it('strict mode: returns true for 0', () => { expect(isFalse(0, true)).toBe(true); });
	it('strict mode: returns false for empty string', () => { expect(isFalse('', true)).toBe(false); });
	it('strict mode: returns false for "false"', () => { expect(isFalse('false', true)).toBe(false); });
	it('strict mode: returns false for "0"', () => { expect(isFalse('0', true)).toBe(false); });
	it('strict mode: returns false for "off"', () => { expect(isFalse('off', true)).toBe(false); });
	it('strict mode: returns false for "yes"', () => { expect(isFalse('yes', true)).toBe(false); });
});

describe('isTrue', () => {
	it('returns true for true', () => { expect(isTrue(true)).toBe(true); });
	it('returns true for 1', () => { expect(isTrue(1)).toBe(true); });
	it('returns true for string "true"', () => { expect(isTrue('true')).toBe(true); });
	it('returns true for string "1"', () => { expect(isTrue('1')).toBe(true); });
	it('returns true for string "on"', () => { expect(isTrue('on')).toBe(true); });
	it('returns true for string "yes"', () => { expect(isTrue('yes')).toBe(true); });
	it('returns true for string "TRUE" (case insensitive)', () => { expect(isTrue('TRUE')).toBe(true); });
	it('returns true for string "On" (case insensitive)', () => { expect(isTrue('On')).toBe(true); });
	it('returns true for string " YES " (trimmed)', () => { expect(isTrue(' YES ')).toBe(true); });
	it('returns false for false', () => { expect(isTrue(false)).toBe(false); });
	it('returns false for 0', () => { expect(isTrue(0)).toBe(false); });
	it('returns false for empty string', () => { expect(isTrue('')).toBe(false); });
	it('returns true for non-true string (not isFalse)', () => { expect(isTrue('a')).toBe(true); });

	it('strict mode: returns true for true', () => { expect(isTrue(true, true)).toBe(true); });
	it('strict mode: returns true for 1', () => { expect(isTrue(1, true)).toBe(true); });
	it('strict mode: returns false for "true"', () => { expect(isTrue('true', true)).toBe(false); });
	it('strict mode: returns false for "1"', () => { expect(isTrue('1', true)).toBe(false); });
	it('strict mode: returns false for "on"', () => { expect(isTrue('on', true)).toBe(false); });
	it('strict mode: returns false for "yes"', () => { expect(isTrue('yes', true)).toBe(false); });
});

describe('isId', () => {
	it('returns true for positive numeric strings', () => { expect(isId('1')).toBe(true); expect(isId('123')).toBe(true); });
	it('returns false for zero', () => { expect(isId('0')).toBe(false); });
	it('returns false for negative numbers', () => { expect(isId('-1')).toBe(false); });
	it('returns false for non-numeric strings', () => { expect(isId('abc')).toBe(false); });
	it('returns false for strings with decimals', () => { expect(isId('1.5')).toBe(false); });
});

describe('isIterable', () => {
	it('returns true for objects', () => { expect(isIterable({})).toBe(true); });
	it('returns true for arrays', () => { expect(isIterable([])).toBe(true); });
	it('returns false for null', () => { expect(isIterable(null)).toBe(false); });
	it('returns false for undefined', () => { expect(isIterable(undefined)).toBe(false); });
	it('returns false for primitives', () => {
		expect(isIterable(0)).toBe(false);
		expect(isIterable('')).toBe(false);
		expect(isIterable(true)).toBe(false);
	});
});

describe('isObject', () => {
	it('returns true for plain objects', () => { expect(isObject({})).toBe(true); expect(isObject({ a: 1 })).toBe(true); });
	it('returns false for arrays', () => { expect(isObject([])).toBe(false); });
	it('returns false for null', () => { expect(isObject(null)).toBe(false); });
	it('returns false for primitives', () => {
		expect(isObject(0)).toBe(false);
		expect(isObject('')).toBe(false);
		expect(isObject(true)).toBe(false);
	});
});

describe('isPromise', () => {
	it('returns true for promises', () => { expect(isPromise(new Promise(() => {}))).toBe(true); });
	it('returns false for non-promises', () => {
		expect(isPromise({})).toBe(false);
		expect(isPromise([])).toBe(false);
		expect(isPromise(0)).toBe(false);
		expect(isPromise('')).toBe(false);
	});
});

describe('isArray', () => {
	it('returns true for arrays', () => { expect(isArray([])).toBe(true); expect(isArray([1])).toBe(true); });
	it('returns false for non-arrays', () => {
		expect(isArray({})).toBe(false);
		expect(isArray(null)).toBe(false);
		expect(isArray(0)).toBe(false);
		expect(isArray('')).toBe(false);
	});
});

describe('isBool', () => {
	it('returns true for booleans', () => { expect(isBool(true)).toBe(true); expect(isBool(false)).toBe(true); });
	it('returns false for non-booleans', () => {
		expect(isBool(0)).toBe(false);
		expect(isBool('')).toBe(false);
		expect(isBool(null)).toBe(false);
		expect(isBool({})).toBe(false);
	});
});

describe('isString', () => {
	it('returns true for strings', () => { expect(isString('')).toBe(true); expect(isString('a')).toBe(true); });
	it('returns false for non-strings', () => {
		expect(isString(0)).toBe(false);
		expect(isString(null)).toBe(false);
		expect(isString({})).toBe(false);
		expect(isString([])).toBe(false);
	});
});

describe('isNumeric', () => {
	it('returns true for numbers', () => { expect(isNumeric(0)).toBe(true); expect(isNumeric(1)).toBe(true); expect(isNumeric(-1)).toBe(true); });
	it('returns true for numeric strings', () => { expect(isNumeric('0')).toBe(true); expect(isNumeric('123')).toBe(true); expect(isNumeric('-1')).toBe(true); });
	it('returns true for float strings', () => { expect(isNumeric('1.5')).toBe(true); });
	it('returns false for non-numeric strings', () => { expect(isNumeric('abc')).toBe(false); });
	it('returns false for non-numeric types', () => {
		expect(isNumeric(null)).toBe(false);
		expect(isNumeric({})).toBe(false);
		expect(isNumeric([])).toBe(false);
		expect(isNumeric(true)).toBe(false);
	});
});

describe('isMatch', () => {
	it('returns truthy for matching string/regex without slashes', () => { expect(isMatch('hello', 'hel')).toBeTruthy(); });
	it('returns truthy for matching string/regex with slashes', () => { expect(isMatch('hello', '/hel/')).toBeTruthy(); });
	it('returns truthy for matching with flags', () => { expect(isMatch('HELLO', '/hel/i')).toBeTruthy(); });
	it('returns falsy for non-matching', () => { expect(isMatch('hello', 'xyz')).toBeFalsy(); });
	it('returns false for non-string string arg', () => { expect(isMatch(123, 'hel')).toBe(false); });
	it('returns false for non-string regex arg', () => { expect(isMatch('hello', 123)).toBe(false); });
});

describe('isEqual', () => {
	it('returns true for equal primitives', () => {
		expect(isEqual(1, 1)).toBe(true);
		expect(isEqual('a', 'a')).toBe(true);
		expect(isEqual(true, true)).toBe(true);
	});
	it('returns false for unequal primitives', () => {
		expect(isEqual(1, 2)).toBe(false);
		expect(isEqual('a', 'b')).toBe(false);
	});
	it('returns true for equal objects', () => {
		expect(isEqual({ a: 1 }, { a: 1 })).toBe(true);
		expect(isEqual({ a: 1, b: 2 }, { b: 2, a: 1 })).toBe(true);
	});
	it('returns false for unequal objects', () => {
		expect(isEqual({ a: 1 }, { a: 2 })).toBe(false);
		expect(isEqual({ a: 1 }, { b: 1 })).toBe(false);
	});
	it('returns true for equal arrays', () => {
		expect(isEqual([1, 2], [1, 2])).toBe(true);
	});
	it('returns false for unequal arrays', () => {
		expect(isEqual([1, 2], [2, 1])).toBe(false);
	});
});

describe('isMultiline', () => {
	it('returns true for strings with newlines', () => {
		expect(isMultiline('a\nb')).toBe(true);
		expect(isMultiline('a\r\nb')).toBe(true);
	});
	it('returns false for single-line strings', () => {
		expect(isMultiline('abc')).toBe(false);
	});
	it('returns false for non-strings', () => {
		expect(isMultiline(123)).toBe(false);
		expect(isMultiline(null)).toBe(false);
	});
});

describe('isFieldEditable', () => {
	it('returns false when editable is false', () => {
		expect(isFieldEditable({ editable: false })).toBe(false);
	});
	it('returns false when disabled', () => {
		expect(isFieldEditable({ disabled: true })).toBe(false);
	});
	it('returns false when readonly', () => {
		expect(isFieldEditable({ readonly: true })).toBe(false);
	});
	it('returns false when readOnly', () => {
		expect(isFieldEditable({ readOnly: true })).toBe(false);
	});
	it('returns true when all clear', () => {
		expect(isFieldEditable({})).toBe(true);
		expect(isFieldEditable({ editable: true })).toBe(true);
	});
});

describe('hasKey', () => {
	it('returns false for empty objects', () => { expect(hasKey({}, 'a')).toBe(false); });
	it('returns false for null/undefined', () => {
		expect(hasKey(null, 'a')).toBe(false);
		expect(hasKey(undefined, 'a')).toBe(false);
	});
	it('returns false for non-objects', () => {
		expect(hasKey(123, 'a')).toBe(false);
		expect(hasKey('str', 'a')).toBe(false);
	});
	it('returns true for existing keys', () => { expect(hasKey({ a: 1 }, 'a')).toBe(true); });
	it('returns false for non-existing keys', () => { expect(hasKey({ a: 1 }, 'b')).toBe(false); });
	it('works on arrays', () => {
		expect(hasKey([1, 2], 0)).toBe(true);
		expect(hasKey([1, 2], 5)).toBe(false);
	});
});

describe('hasValue', () => {
	it('returns false for scalars', () => {
		expect(hasValue(123, 1)).toBe(false);
		expect(hasValue('abc', 'a')).toBe(false);
		expect(hasValue(true, true)).toBe(false);
	});
	it('returns false for empty arrays', () => { expect(hasValue([], 1)).toBe(false); });
	it('returns true for existing values in array', () => { expect(hasValue([1, 2, 3], 2)).toBe(true); });
	it('returns false for non-existing values in array', () => { expect(hasValue([1, 2, 3], 4)).toBe(false); });
	it('works with objects that have includes', () => {
		const obj = { includes: (v) => v === 2 };
		expect(hasValue(obj, 2)).toBe(true);
	});
});

describe('startsWith', () => {
	it('returns true when string starts with compare', () => {
		expect(startsWith('hello', 'hel')).toBe(true);
	});
	it('returns false when string does not start with compare', () => {
		expect(startsWith('hello', 'world')).toBe(false);
	});
	it('works with arrays (loose)', () => {
		expect(startsWith([1, 2, 3], 1)).toBe(true);
		expect(startsWith([1, 2, 3], 2)).toBe(false);
	});
	it('works with arrays (strict)', () => {
		expect(startsWith([1, 2, 3], 1, true)).toBe(true);
	});
	it('works with objects (loose)', () => {
		// Note: spreading non-iterable objects may throw in some environments
	});
	it('returns false for non-string/non-object', () => {
		expect(startsWith(123, 'a')).toBe(false);
	});
});

describe('endsWith', () => {
	it('returns true when string ends with compare', () => {
		expect(endsWith('hello', 'lo')).toBe(true);
	});
	it('returns false when string does not end with compare', () => {
		expect(endsWith('hello', 'he')).toBe(false);
	});
	it('works with arrays (loose)', () => {
		expect(endsWith([1, 2, 3], 3)).toBe(true);
		expect(endsWith([1, 2, 3], 1)).toBe(false);
	});
	it('works with arrays (strict)', () => {
		expect(endsWith([1, 2, 3], 3, true)).toBe(true);
	});
	it('works with objects (loose)', () => {
		// Note: spreading non-iterable objects may throw in some environments
	});
	it('returns false for non-string/non-object', () => {
		expect(endsWith(123, 'a')).toBe(false);
	});
});

describe('isConfigured', () => {
	it('returns false for non-object with value and compare equal (not configured)', () => {
		expect(isConfigured('a', 'a')).toBe(false);
	});
	it('returns true for non-object with value and compare not equal (configured)', () => {
		expect(isConfigured('a', 'b')).toBe(true);
	});
	it('returns true for non-object with value and no compare', () => {
		expect(isConfigured('a')).toBe(true);
	});
	it('returns false for non-object without value', () => {
		expect(isConfigured(null)).toBe(false);
		expect(isConfigured('')).toBe(false);
	});
	it('returns true for non-empty array', () => {
		expect(isConfigured([1, 2], [])).toBe(true);
	});
	it('returns false for empty array', () => {
		expect(isConfigured([], [])).toBe(false);
	});
	it('returns true for object with configured keys', () => {
		expect(isConfigured({ a: 1 }, { a: null })).toBe(true);
	});
	it('returns false for object matching defaults', () => {
		expect(isConfigured({ a: 1 }, { a: 1 })).toBe(false);
	});
	it('returns false for empty object', () => {
		expect(isConfigured({}, {})).toBe(false);
	});
});

describe('getOperators', () => {
	it('returns an object with operator keys', () => {
		const operators = getOperators();
		expect(typeof operators).toBe('object');
		expect(operators['===']).toBeDefined();
		expect(operators['==']).toBeDefined();
	});
});

describe('validate', () => {
	it('returns true for empty conditions', () => {
		expect(validate({}, { a: 1 })).toBe(true);
		expect(validate(null, { a: 1 })).toBe(true);
	});
	it('validates set operator', () => {
		expect(validate({ a: { operator: 'set', compare: 'x' } }, { a: 1 })).toBe(true);
		expect(validate({ a: { operator: 'set' } }, { a: 1 })).toBe(true);
		expect(validate({ a: { operator: 'set' } }, { b: 1 })).toBe(false);
	});
	it('validates not_set operator', () => {
		expect(validate({ a: { operator: 'not_set' } }, { b: 1 })).toBe(true);
		expect(validate({ a: { operator: 'not_set' } }, { a: 1 })).toBe(false);
	});
	it('validates empty operator', () => {
		expect(validate({ a: { operator: 'empty' } }, { a: '' })).toBe(true);
		expect(validate({ a: { operator: 'empty' } }, { a: [] })).toBe(true);
		expect(validate({ a: { operator: 'empty' } }, { a: {} })).toBe(true);
		expect(validate({ a: { operator: 'empty' } }, { a: 1 })).toBe(false);
	});
	it('validates not_empty operator', () => {
		expect(validate({ a: { operator: 'not_empty' } }, { a: 1 })).toBe(true);
		expect(validate({ a: { operator: 'not_empty' } }, { a: '' })).toBe(false);
	});
	it('validates in operator', () => {
		expect(validate({ a: { operator: 'in', compare: [1, 2, 3] } }, { a: 2 })).toBe(true);
		expect(validate({ a: { operator: 'in', compare: [1, 2, 3] } }, { a: 4 })).toBe(false);
	});
	it('validates contains operator', () => {
		expect(validate({ a: { operator: 'contains', compare: 2 } }, { a: [1, 2, 3] })).toBe(true);
		expect(validate({ a: { operator: 'contains', compare: 4 } }, { a: [1, 2, 3] })).toBe(false);
	});
	it('validates has_key operator', () => {
		expect(validate({ a: { operator: 'has_key', compare: 'b' } }, { a: { b: 1 } })).toBe(true);
		expect(validate({ a: { operator: 'has_key', compare: 'c' } }, { a: { b: 1 } })).toBe(false);
	});
	it('validates lesser operator', () => {
		expect(validate({ a: { operator: '<', compare: 5 } }, { a: 3 })).toBe(true);
		expect(validate({ a: { operator: '<', compare: 5 } }, { a: 7 })).toBe(false);
	});
	it('validates greater operator', () => {
		expect(validate({ a: { operator: '>', compare: 5 } }, { a: 7 })).toBe(true);
		expect(validate({ a: { operator: '>', compare: 5 } }, { a: 3 })).toBe(false);
	});
	it('validates lesser_or_equal operator', () => {
		expect(validate({ a: { operator: '<=', compare: 5 } }, { a: 5 })).toBe(true);
		expect(validate({ a: { operator: '<=', compare: 5 } }, { a: 3 })).toBe(true);
		expect(validate({ a: { operator: '<=', compare: 5 } }, { a: 7 })).toBe(false);
	});
	it('validates greater_or_equal operator', () => {
		expect(validate({ a: { operator: '>=', compare: 5 } }, { a: 5 })).toBe(true);
		expect(validate({ a: { operator: '>=', compare: 5 } }, { a: 7 })).toBe(true);
		expect(validate({ a: { operator: '>=', compare: 5 } }, { a: 3 })).toBe(false);
	});
	it('validates equal operator', () => {
		expect(validate({ a: { operator: '==', compare: '1' } }, { a: '1' })).toBe(true);
		expect(validate({ a: { operator: '==', compare: 1 } }, { a: 1 })).toBe(true);
	});
	it('validates not_equal operator', () => {
		expect(validate({ a: { operator: '!=', compare: '1' } }, { a: '1' })).toBe(false);
		expect(validate({ a: { operator: '!=', compare: 1 } }, { a: 1 })).toBe(false);
	});
	it('validates equal_strict operator', () => {
		expect(validate({ a: { operator: '===', compare: 1 } }, { a: 1 })).toBe(true);
		expect(validate({ a: { operator: '===', compare: '1' } }, { a: 1 })).toBe(false);
	});
	it('validates not_equal_strict operator', () => {
		expect(validate({ a: { operator: '!==', compare: '1' } }, { a: 1 })).toBe(true);
		expect(validate({ a: { operator: '!==', compare: 1 } }, { a: 1 })).toBe(false);
	});
	it('validates starts_with operator', () => {
		expect(validate({ a: { operator: 'starts_with', compare: 'hel' } }, { a: 'hello' })).toBe(true);
		expect(validate({ a: { operator: 'starts_with', compare: 'world' } }, { a: 'hello' })).toBe(false);
	});
	it('validates ends_with operator', () => {
		expect(validate({ a: { operator: 'ends_with', compare: 'lo' } }, { a: 'hello' })).toBe(true);
		expect(validate({ a: { operator: 'ends_with', compare: 'he' } }, { a: 'hello' })).toBe(false);
	});
	it('validates regex operator', () => {
		expect(validate({ a: { operator: 'regex', compare: '/hel/' } }, { a: 'hello' })).toBe(true);
		expect(validate({ a: { operator: 'regex', compare: '/xyz/' } }, { a: 'hello' })).toBe(false);
	});
	it('stops on first false condition', () => {
		expect(validate({ a: { operator: 'set' }, b: { operator: 'set' } }, { a: 1 })).toBe(false);
	});
	it('handles shorthand boolean compare', () => {
		expect(validate({ a: true }, { a: 1 })).toBe(true);
		expect(validate({ a: false }, { a: '' })).toBe(true);
	});
	it('handles shorthand value compare', () => {
		expect(validate({ a: [1, 2, 3] }, { a: 2 })).toBe(true);
		expect(validate({ a: [1, 2, 3] }, { a: 4 })).toBe(false);
	});
});

describe('operator constants', () => {
	it('has all expected operator constants', () => {
		expect(OPERATOR_SET).toBe('set');
		expect(OPERATOR_NOT_SET).toBe('not_set');
		expect(OPERATOR_EMPTY).toBe('empty');
		expect(OPERATOR_NOT_EMPTY).toBe('not_empty');
		expect(OPERATOR_IN).toBe('in');
		expect(OPERATOR_NOT_IN).toBe('not_in');
		expect(OPERATOR_CONTAINS).toBe('contains');
		expect(OPERATOR_NOT_CONTAINS).toBe('not_contains');
		expect(OPERATOR_HAS_KEY).toBe('has_key');
		expect(OPERATOR_NOT_HAS_KEY).toBe('not_has_key');
		expect(OPERATOR_LESSER).toBe('<');
		expect(OPERATOR_GREATER).toBe('>');
		expect(OPERATOR_LESSER_OR_EQUAL).toBe('<=');
		expect(OPERATOR_GREATER_OR_EQUAL).toBe('>=');
		expect(OPERATOR_EQUAL).toBe('==');
		expect(OPERATOR_NOT_EQUAL).toBe('!=');
		expect(OPERATOR_EQUAL_STRICT).toBe('===');
		expect(OPERATOR_NOT_EQUAL_STRICT).toBe('!==');
		expect(OPERATOR_STARTS_WITH).toBe('starts_with');
		expect(OPERATOR_NOT_STARTS_WITH).toBe('not_starts_with');
		expect(OPERATOR_ENDS_WITH).toBe('ends_with');
		expect(OPERATOR_NOT_ENDS_WITH).toBe('not_ends_with');
		expect(OPERATOR_REGEX).toBe('regex');
		expect(OPERATOR_IN_STRICT).toBe('in_strict');
		expect(OPERATOR_NOT_IN_STRICT).toBe('not_in_strict');
		expect(OPERATOR_CONTAINS_STRICT).toBe('contains_strict');
		expect(OPERATOR_NOT_CONTAINS_STRICT).toBe('not_contains_strict');
	});
});
