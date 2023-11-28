import { useEffect, useState } from 'react';
import { isEmpty } from '../utils/conditionals';

/**
 * @param {string} key
 * @param {*} initial
 * @returns {*,function,boolean}
 */
export default function useClipboard( key, initial = {} ) {
	key += 'Clipboard';
	const [ preference, setClipboard ] = useState( sessionStorage.getItem( key ) ?? initial );

	const updateClipboard = ( value ) => {
		sessionStorage.setItem( key, value );
		setClipboard( value );
	}

	return [ preference, updateClipboard, ! isEmpty( sessionStorage.getItem( key ) ) ];
}
