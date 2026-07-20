import { describe, it, expect, vi } from 'vitest';
import {
	createRefId,
	parseId,
	ucfirst,
	sprintf,
	round,
	sleep,
	debug,
} from '@syncengine/utils/globals';

describe('createRefId', () => {
	it('returns a string', () => {
		const id = createRefId();
		expect(typeof id).toBe('string');
		expect(id.length).toBeGreaterThan(0);
	});
	it('includes prefix when provided', () => {
		const id = createRefId('prefix_');
		expect(id.startsWith('prefix_')).toBe(true);
	});
	it('includes postfix when provided', () => {
		const id = createRefId('', '_post');
		expect(id.endsWith('_post')).toBe(true);
	});
	it('returns unique ids on rapid calls', () => {
		const ids = new Set();
		for (let i = 0; i < 100; i++) {
			ids.add(createRefId());
		}
		expect(ids.size).toBe(100);
	});
});

describe('parseId', () => {
	it('parses numeric string', () => {
		expect(parseId('123')).toBe(123);
	});
	it('parses number', () => {
		expect(parseId(456)).toBe(456);
	});
	it('parses object with id property', () => {
		expect(parseId({ id: 789 })).toBe(789);
	});
	it('returns null for NaN', () => {
		expect(parseId('abc')).toBeNull();
	});
	it('returns null for empty value', () => {
		expect(parseId('')).toBeNull();
		expect(parseId(null)).toBeNull();
		expect(parseId(undefined)).toBeNull();
	});
	it('returns null for 0', () => {
		expect(parseId(0)).toBeNull();
	});
});

describe('ucfirst', () => {
	it('uppercases first character', () => {
		expect(ucfirst('hello')).toBe('Hello');
	});
	it('handles already uppercase', () => {
		expect(ucfirst('Hello')).toBe('Hello');
	});
	it('handles single character', () => {
		expect(ucfirst('a')).toBe('A');
	});
	it('handles empty string', () => {
		expect(ucfirst('')).toBe('');
	});
});

describe('sprintf', () => {
	it('replaces single placeholder', () => {
		expect(sprintf('Hello {0}', 'World')).toBe('Hello World');
	});
	it('replaces multiple placeholders', () => {
		expect(sprintf('{0} {1}', 'Hello', 'World')).toBe('Hello World');
	});
	it('replaces numeric placeholders', () => {
		expect(sprintf('{0} {1}', 'A', 'B')).toBe('A B');
	});
	it('handles object as args', () => {
		expect(sprintf('{0} {1}', ['A', 'B'])).toBe('A B');
	});
	it('skips object args', () => {
		const result = sprintf('{0} {1}', [{}, 'B']);
		expect(result).toBe('{0} B');
	});
	it('decodes URI in format string', () => {
		expect(sprintf('%20{0}', 'test')).toBe(' test');
	});
	it('returns original string with no args', () => {
		expect(sprintf('hello')).toBe('hello');
	});
});

describe('round', () => {
	it('rounds to specified decimals', () => {
		expect(round(1.5, 0)).toBe(2);
		expect(round(1.4, 0)).toBe(1);
		expect(round(1.555, 2)).toBe(1.56);
		expect(round(1.005, 2)).toBe(1.01);
	});
	it('rounds to 0 decimals by default', () => {
		expect(round(5.5)).toBe(6);
		expect(round(5.4)).toBe(5);
	});
	it('handles negative decimals', () => {
		expect(round(123.456, -1)).toBe(120);
	});
	it('handles zero', () => {
		expect(round(0, 2)).toBe(0);
	});
});

describe('sleep', () => {
	it('resolves after delay', async () => {
		const start = Date.now();
		await sleep(10);
		const elapsed = Date.now() - start;
		expect(elapsed).toBeGreaterThanOrEqual(10);
	});
});

describe('debug', () => {
	it('logs when debug is enabled', () => {
		const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
		window.SyncEngine = { debug: true };
		debug('test message', { context: 'data' });
		expect(logSpy).toHaveBeenCalledWith('test message', { context: 'data' });
		logSpy.mockRestore();
	});
	it('does not log when debug is disabled', () => {
		const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
		window.SyncEngine = { debug: false };
		debug('test message');
		expect(logSpy).not.toHaveBeenCalled();
		logSpy.mockRestore();
	});

});
