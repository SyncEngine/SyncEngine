import { describe, it, expect } from 'vitest';
import {
	getFormats,
	fromFormat,
	toFormat,
	nl2br,
	br2nl,
} from '@syncengine/utils/format';

describe('getFormats', () => {
	it('returns format labels', () => {
		const formats = getFormats();
		expect(formats.json).toBe('JSON');
		expect(formats.yaml).toBe('YAML');
		expect(formats.url).toBe('URL');
	});
});

describe('toFormat', () => {
	it('converts to JSON', () => {
		expect(toFormat({ a: 1 }, 'json')).toBe('{"a":1}');
	});
	it('returns empty string for empty data', () => {
		expect(toFormat(null, 'json')).toBe('');
		expect(toFormat({}, 'json')).toBe('');
		expect(toFormat('', 'json')).toBe('');
	});
	it('passes options to JSON.stringify', () => {
		const result = toFormat({ a: 1 }, 'json', [null, 2]);
		expect(result).toContain('\n');
	});
	it('converts to YAML', () => {
		const result = toFormat({ a: 1 }, 'yaml');
		expect(result).toContain('a: 1');
	});
	it('converts to URL', () => {
		const result = toFormat({ a: '1', b: '2' }, 'url');
		expect(result).toContain('a=1');
		expect(result).toContain('b=2');
	});
	it('returns data for unknown format', () => {
		const obj = { test: true };
		expect(toFormat(obj, 'unknown')).toBe(obj);
	});
});

describe('fromFormat', () => {
	it('parses JSON', () => {
		expect(fromFormat('{"a":1}', 'json')).toEqual({ a: 1 });
	});
	it('returns empty object for empty string', () => {
		expect(fromFormat('', 'json')).toEqual({});
		expect(fromFormat(null, 'json')).toEqual({});
	});
	it('parses YAML', () => {
		const result = fromFormat('a: 1\n', 'yaml');
		expect(result.a).toBe(1);
	});
	it('parses URL', () => {
		const result = fromFormat('a=1&b=2', 'url');
		expect(result.a).toBe('1');
		expect(result.b).toBe('2');
	});
	it('returns string for unknown format', () => {
		expect(fromFormat('hello', 'unknown')).toBe('hello');
	});
});

describe('nl2br', () => {
	it('converts newlines to <br>', () => {
		expect(nl2br('a\nb')).toBe('a<br>\nb');
	});
	it('converts \r\n to <br>', () => {
		expect(nl2br('a\r\nb')).toBe('a<br>\r\nb');
	});
	it('converts \r to <br>', () => {
		expect(nl2br('a\rb')).toBe('a<br>\rb');
	});
	it('converts multiple newlines', () => {
		expect(nl2br('a\nb\nc')).toBe('a<br>\nb<br>\nc');
	});
	it('handles no newlines', () => {
		expect(nl2br('hello')).toBe('hello');
	});
	it('handles empty string', () => {
		expect(nl2br('')).toBe('');
	});
	it('uses XHTML br when isXhtml is true', () => {
		expect(nl2br('a\nb', false, true)).toBe('a<br />\nb');
	});
	it('uses replace mode', () => {
		const result = nl2br('\na\n', true);
		expect(result).toContain('<br>');
	});
});

describe('br2nl', () => {
	it('converts <br> to empty string', () => {
		expect(br2nl('a<br>b')).toBe('ab');
	});
	it('converts <br /> to empty string', () => {
		expect(br2nl('a<br />b')).toBe('ab');
	});
	it('converts <BR> to empty string', () => {
		expect(br2nl('a<BR>b')).toBe('ab');
	});
	it('converts </br> to empty string', () => {
		expect(br2nl('a</br>b')).toBe('ab');
	});
	it('handles no br tags', () => {
		expect(br2nl('hello')).toBe('hello');
	});
	it('handles empty string', () => {
		expect(br2nl('')).toBe('');
	});
	it('uses replace mode', () => {
		expect(br2nl('a<br>b', true)).toBe('a\nb');
	});
});
