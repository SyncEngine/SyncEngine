import { describe, it, expect } from 'vitest';
import {
	mergeClassNames,
} from '@syncengine/utils/props';

describe('mergeClassNames', () => {
	it('merges multiple class names', () => {
		expect(mergeClassNames('a', 'b', 'c')).toBe('a b c');
	});
	it('filters out falsy values', () => {
		expect(mergeClassNames('a', null, 'b', undefined, '', 'c')).toBe('a b c');
	});
	it('handles empty input', () => {
		expect(mergeClassNames()).toBe('');
		expect(mergeClassNames(null, undefined)).toBe('');
	});
	it('handles single class name', () => {
		expect(mergeClassNames('a')).toBe('a');
	});
	it('duplicates class names', () => {
		expect(mergeClassNames('a', 'a')).toBe('a a');
	});
});
