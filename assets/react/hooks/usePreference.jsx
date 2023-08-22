import { useState } from 'react';

/**
 * @param {string} key
 * @param {*} initial
 * @returns {*,function}
 */
export default function usePreference( key, initial ) {
	const [ preference, setPreference ] = useState( localStorage.getItem( key ) ?? initial );

	const updatePreference = ( value ) => {
		localStorage.setItem( key, value );
		setPreference( value );
	}

	return [ preference, updatePreference ];
}
