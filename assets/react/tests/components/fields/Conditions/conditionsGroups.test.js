import { describe, it, expect } from 'vitest';
import {
	parseConditionsValue,
	transformConditionsOutput,
} from '@syncengine/components/fields/Conditions';

describe('parseConditionsValue', () => {
	it('returns default single AND group for null', () => {
		const result = parseConditionsValue( null );
		expect( result ).toHaveProperty( 'conditions' );
		expect( result ).toHaveProperty( 'operator' );
		expect( result.operator ).toBe( 'AND' );
		expect( result.conditions ).toHaveLength( 1 );
		expect( result.conditions[0].operator ).toBe( 'AND' );
		expect( result.conditions[0].conditions ).toEqual( [] );
		expect( result.conditions[0]._ref ).toBeDefined();
	});

	it('returns default single AND group for undefined', () => {
		const result = parseConditionsValue( undefined );
		expect( result.operator ).toBe( 'AND' );
		expect( result.conditions ).toHaveLength( 1 );
	});

	it('wraps flat array in single AND group', () => {
		const flat = [
			{ key: 'a', operator: '==', compare: '1' },
			{ key: 'b', operator: '==', compare: '2' },
		];
		const result = parseConditionsValue( flat );
		expect( result.operator ).toBe( 'AND' );
		expect( result.conditions ).toHaveLength( 1 );
		expect( result.conditions[0].operator ).toBe( 'AND' );
		expect( result.conditions[0].conditions ).toEqual( flat );
	});

	it('normalizes new format and preserves _ref and _label', () => {
		const input = {
			conditions: [
				{ _ref: 'abc', _label: 'My Group', operator: 'OR', conditions: [] },
			],
			operator: 'OR',
		};
		const result = parseConditionsValue( input );
		expect( result.conditions[0]._ref ).toBe( 'abc' );
		expect( result.conditions[0]._label ).toBe( 'My Group' );
		expect( result.conditions[0].operator ).toBe( 'OR' );
		expect( result.operator ).toBe( 'OR' );
	});

	it('defaults missing operator to AND', () => {
		const input = {
			conditions: [ { _ref: 'x', conditions: [] } ],
		};
		const result = parseConditionsValue( input );
		expect( result.conditions[0].operator ).toBe( 'AND' );
	});

	it('defaults missing conditions to empty array', () => {
		const input = {
			conditions: [ { _ref: 'x', operator: 'OR' } ],
			operator: 'AND',
		};
		const result = parseConditionsValue( input );
		expect( result.conditions[0].conditions ).toEqual( [] );
	});

	it('generates _ref for groups missing it', () => {
		const input = {
			conditions: [ { operator: 'AND', conditions: [] } ],
			operator: 'AND',
		};
		const result = parseConditionsValue( input );
		expect( result.conditions[0]._ref ).toBeDefined();
	});

	it('adds default group when conditions array is empty', () => {
		const input = { conditions: [], operator: 'AND' };
		const result = parseConditionsValue( input );
		expect( result.conditions ).toHaveLength( 1 );
		expect( result.conditions[0].operator ).toBe( 'AND' );
	});

	it('does not regenerate _ref on re-parse (stable)', () => {
		const input = {
			conditions: [ { _ref: 'stable-ref', operator: 'AND', conditions: [] } ],
			operator: 'AND',
		};
		const result1 = parseConditionsValue( input );
		const result2 = parseConditionsValue( result1 );
		expect( result1.conditions[0]._ref ).toBe( 'stable-ref' );
		expect( result2.conditions[0]._ref ).toBe( 'stable-ref' );
	});
});

describe('transformConditionsOutput', () => {
	it('returns flat array for single AND group (backwards compat)', () => {
		const data = {
			conditions: [
				{ operator: 'AND', conditions: [ { key: 'a', operator: '==', compare: '1' } ] },
			],
			operator: 'AND',
		};
		const result = transformConditionsOutput( data );
		expect( Array.isArray( result ) ).toBe( true );
		expect( result ).toEqual( [ { key: 'a', operator: '==', compare: '1' } ] );
	});

	it('returns new format for single OR group (not flat)', () => {
		const data = {
			conditions: [
				{ operator: 'OR', conditions: [ { key: 'a', operator: '==', compare: '1' } ] },
			],
			operator: 'AND',
		};
		const result = transformConditionsOutput( data );
		expect( Array.isArray( result ) ).toBe( false );
		expect( result.conditions ).toHaveLength( 1 );
		expect( result.conditions[0].operator ).toBe( 'OR' );
	});

	it('returns new format for multiple groups', () => {
		const data = {
			conditions: [
				{ operator: 'AND', conditions: [ { key: 'a', operator: '==', compare: '1' } ] },
				{ operator: 'OR', conditions: [ { key: 'b', operator: '==', compare: '2' } ] },
			],
			operator: 'OR',
		};
		const result = transformConditionsOutput( data );
		expect( Array.isArray( result ) ).toBe( false );
		expect( result.conditions ).toHaveLength( 2 );
		expect( result.conditions[0].operator ).toBe( 'AND' );
		expect( result.conditions[1].operator ).toBe( 'OR' );
		expect( result.operator ).toBe( 'OR' );
	});

	it('preserves _label through transform', () => {
		const data = {
			conditions: [
				{ _label: 'My Group', operator: 'AND', conditions: [] },
			],
			operator: 'AND',
		};
		const result = transformConditionsOutput( data );
		expect( result.conditions[0]._label ).toBe( 'My Group' );
	});
});

describe('roundtrip', () => {
	it('flat array → parse → transform → flat array (identity)', () => {
		const flat = [
			{ key: 'a', operator: '==', compare: '1' },
			{ key: 'b', operator: '==', compare: '2' },
		];
		const parsed = parseConditionsValue( flat );
		const output = transformConditionsOutput( parsed );
		expect( output ).toEqual( flat );
	});

	it('new format → parse → transform → new format (identity)', () => {
		const input = {
			conditions: [
				{ _ref: 'abc', _label: 'Test', operator: 'OR', conditions: [ { key: 'x', operator: '==', compare: '1' } ] },
				{ _ref: 'def', _label: 'Also', operator: 'AND', conditions: [ { key: 'y', operator: '==', compare: '2' } ] },
			],
			operator: 'AND',
		};
		const parsed = parseConditionsValue( input );
		const output = transformConditionsOutput( parsed );
		expect( Array.isArray( output ) ).toBe( false );
		expect( output.conditions ).toHaveLength( 2 );
		expect( output.conditions[0]._label ).toBe( 'Test' );
		expect( output.conditions[1]._label ).toBe( 'Also' );
		expect( output.conditions[0].operator ).toBe( 'OR' );
		expect( output.conditions[1].operator ).toBe( 'AND' );
		expect( output.operator ).toBe( 'AND' );
	});

	it('single AND group → parse → transform → flat array (backwards compat)', () => {
		const input = {
			conditions: [
				{ _ref: 'abc', _label: 'Group 1', operator: 'AND', conditions: [ { key: 'a', operator: '==', compare: '1' } ] },
			],
			operator: 'AND',
		};
		const parsed = parseConditionsValue( input );
		const output = transformConditionsOutput( parsed );
		expect( Array.isArray( output ) ).toBe( true );
		expect( output ).toEqual( [ { key: 'a', operator: '==', compare: '1' } ] );
	});
});
