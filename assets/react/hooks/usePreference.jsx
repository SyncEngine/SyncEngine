import { useState } from 'react';
import { isEmpty } from '../utils/conditionals';

/**
 * @param {string} key
 * @param {*} initial
 * @returns {*,function,boolean}
 */
export default function usePreference( key, initial ) {
	const [ preference, setPreference ] = useState( localStorage.getItem( key ) ?? initial );

	const updatePreference = ( value ) => {
		localStorage.setItem( key, value );
		setPreference( value );
	}

	return [ preference, updatePreference, ! isEmpty( localStorage.getItem( key ) ) ];
}
