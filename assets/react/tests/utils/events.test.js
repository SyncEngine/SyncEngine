import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import {
	publish,
	subscribe,
	unsubscribe,
	suppress,
	debounce,
} from '@syncengine/utils/events';

describe('publish', () => {
	it('dispatches a CustomEvent', () => {
		const handler = vi.fn();
		document.addEventListener('test-event', handler);
		publish('test-event', { data: 'value' });
		document.removeEventListener('test-event', handler);
		expect(handler).toHaveBeenCalled();
		expect(handler.mock.calls[0][0].detail).toEqual({ data: 'value' });
	});
});

describe('subscribe', () => {
	it('adds event listener', () => {
		const handler = vi.fn();
		subscribe('test-subscribe', handler);
		document.dispatchEvent(new CustomEvent('test-subscribe', { detail: 'data' }));
		unsubscribe('test-subscribe', handler);
		expect(handler).toHaveBeenCalled();
	});
});

describe('unsubscribe', () => {
	it('removes event listener', () => {
		const handler = vi.fn();
		subscribe('test-unsubscribe', handler);
		unsubscribe('test-unsubscribe', handler);
		document.dispatchEvent(new CustomEvent('test-unsubscribe'));
		expect(handler).not.toHaveBeenCalled();
	});
});

describe('suppress', () => {
	it('prevents default and stops propagation', () => {
		const mockEvent = {
			preventDefault: vi.fn(),
			stopPropagation: vi.fn(),
		};
		suppress(mockEvent);
		expect(mockEvent.preventDefault).toHaveBeenCalled();
		expect(mockEvent.stopPropagation).toHaveBeenCalled();
	});
	it('handles null event', () => {
		expect(() => suppress(null)).not.toThrow();
	});
	it('handles undefined event', () => {
		expect(() => suppress(undefined)).not.toThrow();
	});
});

describe('debounce', () => {
	it('calls callback after delay', async () => {
		let called = false;
		const fn = debounce(() => { called = true; }, 50);
		fn();
		expect(called).toBe(false);
		await new Promise(r => setTimeout(r, 60));
		expect(called).toBe(true);
	});
	it('only calls once on rapid calls', async () => {
		let count = 0;
		const fn = debounce(() => { count++; }, 50);
		fn();
		fn();
		fn();
		await new Promise(r => setTimeout(r, 60));
		expect(count).toBe(1);
	});
	it('passes arguments to callback', async () => {
		let result = null;
		const fn = debounce((a, b) => { result = a + b; }, 50);
		fn(2, 3);
		await new Promise(r => setTimeout(r, 60));
		expect(result).toBe(5);
	});
	it('uses default delay of 250ms', async () => {
		let called = false;
		const fn = debounce(() => { called = true; });
		fn();
		expect(called).toBe(false);
		await new Promise(r => setTimeout(r, 260));
		expect(called).toBe(true);
	});
});
