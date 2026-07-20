import { describe, it, expect } from 'vitest';
import {
	objectToTags,
	parseTagsObject,
	parseTagsRecursive,
	parseTagString,
	parseTag,
	getTagPart,
	getTagParts,
	splitByTags,
	isTag,
	hasTag,
	trimTag,
	TAG_START_CHAR,
	TAG_END_CHAR,
	TAG_SEPARATOR,
} from '@syncengine/utils/tags';

describe('TAG constants', () => {
	it('has correct tag characters', () => {
		expect(TAG_START_CHAR).toBe('{{');
		expect(TAG_END_CHAR).toBe('}}');
		expect(TAG_SEPARATOR).toBe('.');
	});
});

describe('objectToTags', () => {
	it('converts flat object to tags', () => {
		const obj = { a: 1, b: 2 };
		const result = objectToTags(obj);
		expect(result).toEqual({ a: 1, b: 2 });
	});
	it('converts nested object with dot notation', () => {
		const obj = { user: { name: 'John' } };
		const result = objectToTags(obj);
		expect(result['user.name']).toBe('John');
	});
	it('handles arrays with [] notation', () => {
		const obj = { items: [{ name: 'a' }, { name: 'b' }] };
		const result = objectToTags(obj);
		expect(result['items.[].name']).toBe('a'); // Uses first array element
	});
	it('handles parent prefix', () => {
		const obj = { a: 1 };
		const result = objectToTags(obj, 'parent');
		expect(result['parent.a']).toBe(1);
	});
	it('returns empty object for non-objects', () => {
		expect(objectToTags('string')).toEqual({});
		expect(objectToTags(123)).toEqual({});
		expect(objectToTags(null)).toEqual({});
	});
});

describe('parseTagsObject', () => {
	it('looks up string key in parse object', () => {
		const tags = { key: 'name' };
		const parse = { name: 'John' };
		const result = parseTagsObject(tags, parse);
		expect(result.key).toBe('John');
	});
	it('handles nested objects recursively', () => {
		const tags = { user: { name: 'name' } };
		const parse = { name: 'John' };
		const result = parseTagsObject(tags, parse);
		expect(result.user.name).toBe('John');
	});
	it('handles string value lookup', () => {
		const tags = { key: 'name' };
		const parse = { name: 'John' };
		const result = parseTagsObject(tags, parse);
		expect(result.key).toBe('John');
	});
	it('returns empty string for missing key', () => {
		const tags = { key: 'missing' };
		const parse = { name: 'John' };
		const result = parseTagsObject(tags, parse);
		expect(result.key).toBe('');
	});
});

describe('parseTagString', () => {
	it('parses single tag', () => {
		const result = parseTagString('{{ name }}', { name: 'John' });
		expect(result).toBe('John');
	});
	it('preserves surrounding text', () => {
		const result = parseTagString('Hello {{ name }}!', { name: 'John' });
		expect(result).toBe('Hello John!');
	});
	it('returns undefined for no resource', () => {
		expect(parseTagString('{{ name }}', null)).toBeUndefined();
	});
	it('handles multiple tags', () => {
		const result = parseTagString('{{ a }} {{ b }}', { a: 'X', b: 'Y' });
		expect(result).toBe('X Y');
	});
});

describe('parseTag', () => {
	it('looks up flat key', () => {
		expect(parseTag('name', { name: 'John' })).toBe('John');
	});
	it('looks up nested key with dot notation', () => {
		expect(parseTag('user.name', { user: { name: 'John' } })).toBe('John');
	});
	it('returns empty string for missing key', () => {
		expect(parseTag('missing', { name: 'John' })).toBe('');
	});
	it('returns empty string for missing nested key', () => {
		expect(parseTag('user.missing', { user: { name: 'John' } })).toBe('');
	});
	it('handles deep nesting', () => {
		const resource = { a: { b: { c: 'deep' } } };
		expect(parseTag('a.b.c', resource)).toBe('deep');
	});
});

describe('getTagPart', () => {
	it('returns first part', () => {
		expect(getTagPart('a.b.c', 0)).toBe('a');
	});
	it('returns second part', () => {
		expect(getTagPart('a.b.c', 1)).toBe('b');
	});
	it('returns third part', () => {
		expect(getTagPart('a.b.c', 2)).toBe('c');
	});
	it('returns null for out of bounds', () => {
		expect(getTagPart('a.b', 5)).toBeNull();
	});
});

describe('getTagParts', () => {
	it('splits tag by separator', () => {
		expect(getTagParts('a.b.c')).toEqual(['a', 'b', 'c']);
	});
	it('handles single part', () => {
		expect(getTagParts('a')).toEqual(['a']);
	});
	it('trims outer whitespace only', () => {
		expect(getTagParts(' a . b ')).toEqual(['a ', ' b']);
	});
});

describe('splitByTags', () => {
	it('splits string with single tag', () => {
		const result = splitByTags('Hello {{ name }}!');
		expect(result).toEqual(['Hello ', '{{ name }}', '!']);
	});
	it('splits string with no tags', () => {
		const result = splitByTags('Hello world');
		expect(result).toEqual(['Hello world']);
	});
	it('splits string with multiple tags', () => {
		const result = splitByTags('{{ a }} {{ b }}');
		expect(result).toEqual(['{{ a }}', ' ', '{{ b }}']);
	});
	it('handles nested tags', () => {
		const result = splitByTags('{{ outer<{ inner }> }}');
		expect(result.length).toBeGreaterThan(0);
	});
	it('handles malformed tags gracefully', () => {
		const result = splitByTags('{{ unclosed');
		expect(result.length).toBeGreaterThan(0);
	});
});

describe('isTag', () => {
	it('returns true for valid tag', () => {
		expect(isTag('{{ name }}')).toBe(true);
	});
	it('returns false for non-string', () => {
		expect(isTag(123)).toBe(false);
		expect(isTag(null)).toBe(false);
		expect(isTag({})).toBe(false);
	});
	it('returns false for string without braces', () => {
		expect(isTag('name')).toBe(false);
	});
	it('respects validateTagChars', () => {
		expect(isTag('name.something', false)).toBe(true);
	});
});

describe('hasTag', () => {
	it('returns true when tag exists', () => {
		expect(hasTag('Hello {{ name }}')).toBe(true);
	});
	it('returns false when no tag', () => {
		expect(hasTag('Hello world')).toBe(false);
	});
	it('returns false for non-string', () => {
		expect(hasTag(123)).toBe(false);
		expect(hasTag(null)).toBe(false);
	});
	it('returns false when end comes before start', () => {
		expect(hasTag('}} {{')).toBe(false);
	});
});

describe('trimTag', () => {
	it('removes tag delimiters and whitespace', () => {
		expect(trimTag('{{ name }}')).toBe('name');
	});
	it('handles already trimmed', () => {
		expect(trimTag('name')).toBe('name');
	});
	it('handles nested tag syntax', () => {
		const result = trimTag('{{ user.name }}');
		expect(result).toBe('user.name');
	});
});
