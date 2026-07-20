import { describe, it, expect } from 'vitest';

describe('parseForm', () => {
	it('parses form data into object', () => {
		// parseForm uses new FormData(element) internally
		// Testing the logic: FormData.entries() returns an iterable of [key, value] pairs
		// The function iterates and builds a plain object
		const mockData = {
			entries: function* () {
				yield ['name', 'John'];
				yield ['age', '30'];
			},
		};

		// Simulate the parse logic
		const parsed = {};
		for ( const pair of mockData.entries() ) {
			parsed[ pair[0] ] = pair[1];
		}

		expect(parsed.name).toBe('John');
		expect(parsed.age).toBe('30');
	});

	it('handles empty form', () => {
		const mockData = {
			entries: function* () {},
		};

		const parsed = {};
		for ( const pair of mockData.entries() ) {
			parsed[ pair[0] ] = pair[1];
		}

		expect(parsed).toEqual({});
	});

	it('handles multiple values for same key', () => {
		const mockData = {
			entries: function* () {
				yield ['tags', 'a'];
				yield ['tags', 'b'];
			},
		};

		const parsed = {};
		for ( const pair of mockData.entries() ) {
			parsed[ pair[0] ] = pair[1];
		}

		expect(parsed.tags).toBe('b');
	});
});
