import { describe, it, expect } from 'vitest';
import {
	trim,
	trimWord,
} from '@syncengine/utils/trim';

describe('trim', () => {
	it('trims from start', () => {
		expect(trim('   hello', ' ')).toBe('hello');
	});
	it('trims from end', () => {
		expect(trim('hello   ', ' ')).toBe('hello');
	});
	it('trims from both sides', () => {
		expect(trim('   hello   ', ' ')).toBe('hello');
	});
	it('trims multiple characters', () => {
		expect(trim('xyzhelloxyz', 'xyz')).toBe('hello');
	});
	it('handles no trimming needed', () => {
		expect(trim('hello', 'xyz')).toBe('hello');
	});
	it('handles empty string', () => {
		expect(trim('', ' ')).toBe('');
	});
	it('handles empty chars', () => {
		expect(trim('hello', '')).toBe('hello');
	});
	it('trims custom chars', () => {
		expect(trim('...hello...', '.')).toBe('hello');
	});
});

describe('trimWord', () => {
	it('trims word from start', () => {
		expect(trimWord('prefixhello', 'prefix')).toBe('hello');
	});
	it('trims word from end', () => {
		expect(trimWord('hello suffix', 'suffix')).toBe('hello ');
	});
	it('trims word from both sides', () => {
		expect(trimWord('prefixhello suffix', 'prefix', )).toBe('hello suffix');
	});
	it('handles no match', () => {
		expect(trimWord('hello', 'xyz')).toBe('hello');
	});
	it('handles empty string', () => {
		expect(trimWord('', 'prefix')).toBe('');
	});
	it('handles empty word', () => {
		expect(trimWord('hello', '')).toBe('hello');
	});
});
