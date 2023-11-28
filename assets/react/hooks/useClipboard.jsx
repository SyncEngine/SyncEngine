import { useCallback, useState } from 'react';
import { isEmpty } from '../utils/conditionals';
import { publish, subscribe } from '../utils/events';

/**
 * @param {string} key
 * @param {*} initial
 * @returns {*,function,boolean}
 */
export default function useClipboard( key, initial = null ) {
	key += 'Clipboard';

	const get = useCallback( ( fallback ) => {
		let value = sessionStorage.getItem( key );
		if ( null === value ) {
			return fallback;
		}
		return JSON.parse( value );
	}, [ key ] );

	const set = useCallback( ( value ) => {
		sessionStorage.setItem( key, JSON.stringify( value ) );
	}, [ key ] );

	const [ preference, setClipboard ] = useState( get( initial ) );

	const updateClipboard = ( value ) => {
		set( value );
		publish( 'update_' + key, value );
	}

	subscribe( 'update_' + key, () => {
		setClipboard( get() );
	} );

	return [ preference, updateClipboard, ! isEmpty( get() ) ];
}
