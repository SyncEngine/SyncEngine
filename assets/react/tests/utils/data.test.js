import { describe, it, expect } from 'vitest';
import { deepClone, deepEqual, mapFilter, mapFind, mapGetProp, objectToMappable } from '@syncengine/utils/data';

describe('deepClone', () => {
	it('clones arrays deeply', () => {
		const original = [1, { nested: [2, 3] }];
		const cloned = deepClone(original);
		expect(cloned).toEqual(original);
		expect(cloned).not.toBe(original);
		expect(cloned[1]).not.toBe(original[1]);
		expect(cloned[1].nested).not.toBe(original[1].nested);
	});

	it('clones objects deeply', () => {
		const original = { a: 1, b: { c: 2 } };
		const cloned = deepClone(original);
		expect(cloned).toEqual(original);
		expect(cloned.b).not.toBe(original.b);
	});

	it('returns primitives unchanged', () => {
		expect(deepClone(42)).toBe(42);
		expect(deepClone('hello')).toBe('hello');
		expect(deepClone(null)).toBe(null);
	});
});

describe('deepEqual', () => {
	it('compares objects recursively', () => {
		const a = { x: 1, y: { z: 2 } };
		const b = { x: 1, y: { z: 2 } };
		const c = { x: 1, y: { z: 3 } };
		expect(deepEqual(a, b)).toBe(true);
		expect(deepEqual(a, c)).toBe(false);
	});

	it('compares arrays', () => {
		expect(deepEqual([1, 2], [1, 2])).toBe(true);
		expect(deepEqual([1, 2], [2, 1])).toBe(false);
	});
});

describe('mapFilter', () => {
	it('filters by key-value pair', () => {
		const data = [{ id: 1, active: true }, { id: 2, active: false }];
		const result = mapFilter(data, { active: true });
		expect(result).toHaveLength(1);
		expect(result[0].id).toBe(1);
	});

	it('supports key as second param, value as third', () => {
		const data = [{ id: 1, active: true }, { id: 2, active: false }];
		const result = mapFilter(data, 'active', true);
		expect(result).toHaveLength(1);
	});
});

describe('mapFind', () => {
	it('finds item by property value', () => {
		const data = [{ id: 1, name: 'A' }, { id: 2, name: 'B' }];
		const result = mapFind(data, 2, 'id');
		expect(result).toEqual({ id: 2, name: 'B' });
	});
});

describe('mapGetProp', () => {
	it('extracts a property from all items', () => {
		const data = [{ id: 1 }, { id: 2 }];
		expect(mapGetProp(data, 'id')).toEqual([1, 2]);
	});
});

describe('objectToMappable', () => {
	it('converts object to array with key prop', () => {
		const obj = { a: { name: 'A' }, b: { name: 'B' } };
		const result = objectToMappable(obj, 'key');
		expect(result).toHaveLength(2);
		expect(result[0].key).toBe('a');
		expect(result[1].key).toBe('b');
	});

	it('converts array to mappable array', () => {
		const arr = [1, 2, 3];
		const result = objectToMappable(arr, 'value');
		expect(result).toHaveLength(3);
		expect(result[0].value).toBe(1);
	});
});
