import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import useSyncedState from '@syncengine/hooks/useSyncedState';

describe('useSyncedState', () => {
	beforeEach(() => {
		document.dispatchEvent(new CustomEvent('cleanup'));
	});

	it('returns initial state when null', () => {
		const { result } = renderHook(() => useSyncedState('test:event', null));
		expect(result.current[0]).toBeNull();
	});

	it('returns initial value when provided', () => {
		const { result } = renderHook(() => useSyncedState('test:event', 'default'));
		expect(result.current[0]).toBe('default');
	});

	it('updates state via update function', () => {
		const { result } = renderHook(() => useSyncedState('test:event', null));
		act(() => {
			result.current[1]('new value', true);
		});
		expect(result.current[0]).toBe('new value');
	});
});
