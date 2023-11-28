import { useState } from 'react';
import { isEmpty } from '../utils/conditionals';
import { publish, subscribe } from '../utils/events';

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
		publish( 'update_' + key, value );
	}

	subscribe( 'update_' + key, () => {
		setClipboard( sessionStorage.getItem( key ) );
	} );

	return [ preference, updateClipboard, ! isEmpty( sessionStorage.getItem( key ) ) ];
}
