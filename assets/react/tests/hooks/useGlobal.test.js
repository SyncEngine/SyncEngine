import { describe, it, expect, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import useGlobal from '@syncengine/hooks/useGlobal';

describe('useGlobal', () => {
	it('returns the full SyncEngine object when no name given', () => {
		window.SyncEngine = { foo: 'bar', baz: 42 };
		const { result } = renderHook(() => useGlobal());
		expect(result.current).toBe(window.SyncEngine);
		delete window.SyncEngine;
	});

	it('returns a specific property by name', () => {
		window.SyncEngine = { foo: 'bar', baz: 42 };
		const { result } = renderHook(() => useGlobal('foo'));
		expect(result.current).toBe('bar');
		delete window.SyncEngine;
	});

	it('returns null for missing property', () => {
		window.SyncEngine = { foo: 'bar' };
		const { result } = renderHook(() => useGlobal('missing'));
		expect(result.current).toBeNull();
		delete window.SyncEngine;
	});
});
