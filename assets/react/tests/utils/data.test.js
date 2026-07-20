import { describe, it, expect } from 'vitest';
import {
	deepClone,
	objectToMappable,
	objectKeyToProp,
	objectMerge,
	objectMergeDepth,
	objectFilter,
	deepEqual,
	objectDiff,
	objectIntersect,
	listRenameProp,
	mapGetProp,
	mapGetIndex,
	mapFind,
	mapFilter,
	mapGroupBy,
	mapSortBy,
} from '@syncengine/utils/data';

describe('deepClone', () => {
	it('returns primitives as-is', () => {
		expect(deepClone(1)).toBe(1);
		expect(deepClone('a')).toBe('a');
		expect(deepClone(null)).toBe(null);
		expect(deepClone(undefined)).toBe(undefined);
	});
	it('deep clones arrays', () => {
		const original = [1, [2, 3], { a: 4 }];
		const cloned = deepClone(original);
		expect(cloned).toEqual(original);
		expect(cloned).not.toBe(original);
		expect(cloned[1]).not.toBe(original[1]);
		expect(cloned[2]).not.toBe(original[2]);
	});
	it('deep clones objects', () => {
		const original = { a: 1, b: { c: 2 } };
		const cloned = deepClone(original);
		expect(cloned).toEqual(original);
		expect(cloned).not.toBe(original);
		expect(cloned.b).not.toBe(original.b);
	});
	it('handles undefined values in arrays', () => {
		const original = [1, undefined, 3];
		const cloned = deepClone(original);
		expect(cloned[0]).toBe(1);
		expect(cloned[1]).toBeNull();
		expect(cloned[2]).toBe(3);
	});
	it('handles undefined values in objects', () => {
		const original = { a: 1, b: undefined, c: 3 };
		const cloned = deepClone(original);
		expect(cloned).toEqual({ a: 1, c: 3 });
	});
});

describe('objectToMappable', () => {
	it('returns mapped array from object', () => {
		const obj = { a: 1, b: 2 };
		const result = objectToMappable(obj, 'id', 'value');
		expect(result).toHaveLength(2);
		expect(result[0]).toEqual({ id: 'a', value: 1 });
		expect(result[1]).toEqual({ id: 'b', value: 2 });
	});
	it('handles arrays of primitives', () => {
		const arr = ['a', 'b', 'c'];
		const result = objectToMappable(arr, 'id', 'value');
		expect(result).toHaveLength(3);
		expect(result[0]).toEqual({ id: 'a', value: 'a' });
	});
	it('handles arrays of objects', () => {
		const arr = [{ name: 'a' }, { name: 'b' }];
		const result = objectToMappable(arr, 'id');
		// When array items are objects, keyProp is not added to them
		expect(result[0].name).toBe('a');
		expect(result[1].name).toBe('b');
	});
	it('returns empty array for non-object/non-array', () => {
		expect(objectToMappable(null, 'id')).toEqual([]);
	});
});

describe('objectKeyToProp', () => {
	it('adds key as property to each value', () => {
		const obj = { a: { name: 'A' }, b: { name: 'B' } };
		const result = objectKeyToProp(obj, 'id');
		expect(result.a.id).toBe('a');
		expect(result.b.id).toBe('b');
	});
	it('does not overwrite existing keyProp', () => {
		const obj = { a: { id: 'existing' }, b: { name: 'B' } };
		const result = objectKeyToProp(obj, 'id');
		expect(result.a.id).toBe('existing');
		expect(result.b.id).toBe('b');
	});
});

describe('objectMerge', () => {
	it('merges two objects', () => {
		const result = objectMerge({ a: 1 }, { b: 2 });
		expect(result).toEqual({ a: 1, b: 2 });
	});
	it('overwrites conflicting keys', () => {
		const result = objectMerge({ a: 1 }, { a: 2 });
		expect(result.a).toBe(2);
	});
	it('merges nested objects by reference (no depth)', () => {
		const target = { a: { b: 1 } };
		const source = { a: { c: 2 } };
		const result = objectMerge(target, source);
		expect(result.a).toBe(target.a);
		expect(result.a.b).toBe(1);
		expect(result.a.c).toBe(2);
	});
	it('handles multiple sources', () => {
		const result = objectMerge({ a: 1 }, { b: 2 }, { c: 3 });
		expect(result).toEqual({ a: 1, b: 2, c: 3 });
	});
	it('returns target when no sources', () => {
		const target = { a: 1 };
		expect(objectMerge(target)).toBe(target);
	});
});

describe('objectMergeDepth', () => {
	it('merges to specified depth', () => {
		const target = { a: { b: { c: 1 } } };
		const source = { a: { b: { d: 2 } } };
		const result = objectMergeDepth(target, 2, source);
		// At depth 2, nested objects are replaced at depth 1
		expect(result.a.b).toEqual({ d: 2 });
	});
	it('stops merging at depth 1', () => {
		const target = { a: { b: 1 } };
		const source = { a: { b: 2, c: 3 } };
		const result = objectMergeDepth(target, 1, source);
		expect(result.a).toEqual(source.a);
	});
	it('returns target for depth 0', () => {
		const target = { a: 1 };
		const source = { b: 2 };
		const result = objectMergeDepth(target, 0, source);
		expect(result).toBe(target);
	});
});

describe('objectFilter', () => {
	it('filters object by callback', () => {
		const obj = { a: 1, b: 2, c: 3 };
		const result = objectFilter(obj, (v) => v > 1);
		expect(result).toEqual({ b: 2, c: 3 });
	});
	it('returns empty object for no matches', () => {
		const obj = { a: 1, b: 2 };
		const result = objectFilter(obj, (v) => v > 10);
		expect(result).toEqual({});
	});
	it('skips non-enumerable properties', () => {
		const obj = { a: 1 };
		Object.defineProperty(obj, 'b', { value: 2, enumerable: false });
		const result = objectFilter(obj, () => true);
		expect(result).toEqual({ a: 1 });
	});
});

describe('deepEqual', () => {
	it('returns true for equal primitives', () => {
		expect(deepEqual(1, 1)).toBe(true);
		expect(deepEqual('a', 'a')).toBe(true);
		expect(deepEqual(true, true)).toBe(true);
	});
	it('returns false for unequal primitives', () => {
		expect(deepEqual(1, 2)).toBe(false);
		expect(deepEqual('a', 'b')).toBe(false);
	});
	it('returns true for equal objects', () => {
		expect(deepEqual({ a: 1 }, { a: 1 })).toBe(true);
	});
	it('returns false for unequal objects', () => {
		expect(deepEqual({ a: 1 }, { a: 2 })).toBe(false);
		expect(deepEqual({ a: 1 }, { b: 1 })).toBe(false);
	});
	it('returns true for equal arrays', () => {
		expect(deepEqual([1, 2], [1, 2])).toBe(true);
	});
	it('returns false for unequal arrays', () => {
		expect(deepEqual([1, 2], [2, 1])).toBe(false);
	});
	it('returns false when one is null', () => {
		expect(deepEqual(null, {})).toBe(false);
		expect(deepEqual({}, null)).toBe(false);
	});
	it('returns false when one is array and other is object', () => {
		expect(deepEqual([], {})).toBe(false);
		expect(deepEqual({}, [])).toBe(false);
	});
	it('returns false for different key counts', () => {
		expect(deepEqual({ a: 1, b: 2 }, { a: 1 })).toBe(false);
	});
	it('handles nested equal objects', () => {
		expect(deepEqual({ a: { b: 1 } }, { a: { b: 1 } })).toBe(true);
	});
	it('handles nested unequal objects', () => {
		expect(deepEqual({ a: { b: 1 } }, { a: { b: 2 } })).toBe(false);
	});
});

describe('objectDiff', () => {
	it('returns keys only in obj1', () => {
		const result = objectDiff({ a: 1, b: 2, c: 3 }, { b: 2, d: 4 });
		expect(result).toEqual({ a: 1, c: 3 });
	});
	it('returns empty object for equal objects', () => {
		const result = objectDiff({ a: 1 }, { a: 1 });
		expect(result).toEqual({});
	});
});

describe('objectIntersect', () => {
	it('returns common keys from obj1', () => {
		const result = objectIntersect({ a: 1, b: 2, c: 3 }, { b: 2, d: 4 });
		expect(result).toEqual({ b: 2 });
	});
	it('returns empty object for no common keys', () => {
		const result = objectIntersect({ a: 1 }, { b: 2 });
		expect(result).toEqual({});
	});
});

describe('listRenameProp', () => {
	it('renames existing prop', () => {
		const list = [{ old: 'a' }, { old: 'b' }];
		const result = listRenameProp(list, 'old', 'new');
		expect(result[0]).toEqual({ old: 'a', new: 'a' });
		expect(result[1]).toEqual({ old: 'b', new: 'b' });
	});
	it('uses default for missing props', () => {
		const list = [{ old: 'a' }, { missing: 'b' }];
		const result = listRenameProp(list, 'old', 'new', 'default');
		expect(result[0]).toEqual({ old: 'a', new: 'a' });
		expect(result[1]).toEqual({ missing: 'b', new: 'default' });
	});
	it('does not modify original', () => {
		const list = [{ old: 'a' }];
		const result = listRenameProp(list, 'old', 'new');
		expect(list[0].old).toBe('a');
	});
});

describe('mapGetProp', () => {
	it('extracts property from all items', () => {
		const data = [{ id: 1 }, { id: 2 }, { id: 3 }];
		expect(mapGetProp(data, 'id')).toEqual([1, 2, 3]);
	});
	it('handles empty data', () => {
		expect(mapGetProp([], 'id')).toEqual([]);
	});
});

describe('mapGetIndex', () => {
	it('finds index of value', () => {
		const data = [{ id: 1 }, { id: 2 }, { id: 3 }];
		expect(mapGetIndex(data, 2, 'id')).toBe(1);
	});
	it('returns -1 for not found', () => {
		const data = [{ id: 1 }, { id: 2 }];
		expect(mapGetIndex(data, 3, 'id')).toBe(-1);
	});
	it('works without prop (direct values)', () => {
		const data = [1, 2, 3];
		expect(mapGetIndex(data, 2)).toBe(1);
	});
});

describe('mapFind', () => {
	it('finds item by property value', () => {
		const data = [{ id: 1, name: 'a' }, { id: 2, name: 'b' }];
		expect(mapFind(data, 1, 'id')).toEqual({ id: 1, name: 'a' });
	});
	it('returns fallback for not found', () => {
		const data = [{ id: 1 }];
		expect(mapFind(data, 99, 'id', 'not-found')).toBe('not-found');
	});
	it('returns null fallback by default', () => {
		const data = [{ id: 1 }];
		expect(mapFind(data, 99, 'id')).toBeNull();
	});
	it('works without prop (direct values)', () => {
		const data = [1, 2, 3];
		expect(mapFind(data, 2)).toBe(2);
	});
});

describe('mapFilter', () => {
	it('filters by single key-value', () => {
		const data = [{ status: 'active' }, { status: 'inactive' }, { status: 'active' }];
		const result = mapFilter(data, 'status', 'active');
		expect(result).toHaveLength(2);
	});
	it('filters by object', () => {
		const data = [{ status: 'active', type: 'user' }, { status: 'inactive', type: 'user' }, { status: 'active', type: 'admin' }];
		const result = mapFilter(data, { status: 'active' });
		expect(result).toHaveLength(2);
	});
	it('filters by multiple keys', () => {
		const data = [{ status: 'active', type: 'user' }, { status: 'inactive', type: 'user' }, { status: 'active', type: 'admin' }];
		const result = mapFilter(data, { status: 'active', type: 'admin' });
		expect(result).toHaveLength(1);
		expect(result[0].type).toBe('admin');
	});
	it('returns data for null filters', () => {
		const data = [{ a: 1 }];
		expect(mapFilter(data, null)).toBe(data);
		expect(mapFilter(data, undefined)).toBe(data);
	});
	it('returns data when row missing filter key', () => {
		const data = [{ a: 1 }, { b: 2 }];
		const result = mapFilter(data, 'a', 1);
		expect(result).toHaveLength(1);
	});
});

describe('mapGroupBy', () => {
	it('groups by key', () => {
		const list = [{ group: 'a' }, { group: 'b' }, { group: 'a' }];
		const result = mapGroupBy(list, 'group');
		expect(result.a).toHaveLength(2);
		expect(result.b).toHaveLength(1);
	});
	it('uses fallback for missing key', () => {
		const list = [{ group: 'a' }, { other: 'b' }];
		const result = mapGroupBy(list, 'group', 'unknown');
		expect(result.unknown).toHaveLength(1);
	});
});

describe('mapSortBy', () => {
	it('sorts ascending by default', () => {
		const list = [{ name: 'c' }, { name: 'a' }, { name: 'b' }];
		const result = mapSortBy(list, 'name');
		expect(result[0].name).toBe('a');
		expect(result[2].name).toBe('c');
	});
	it('sorts descending', () => {
		const list = [{ name: 'a' }, { name: 'b' }, { name: 'c' }];
		const result = mapSortBy(list, 'name', true);
		expect(result[0].name).toBe('c');
		expect(result[2].name).toBe('a');
	});
	it('sorts numbers', () => {
		const list = [{ val: 30 }, { val: 10 }, { val: 20 }];
		const result = mapSortBy(list, 'val');
		expect(result[0].val).toBe(10);
		expect(result[2].val).toBe(30);
	});
	it('sorts numbers', () => {
		const list = [{ val: 30 }, { val: 10 }, { val: 20 }];
		const result = mapSortBy(list, 'val', true);
		expect(result[0].val).toBe(30);
		expect(result[2].val).toBe(10);
	});
	it('mutates original (uses list.sort)', () => {
		const list = [{ name: 'c' }, { name: 'a' }];
		mapSortBy(list, 'name');
		expect(list[0].name).toBe('a');
		expect(list[1].name).toBe('c');
	});
});
