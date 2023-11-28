import { useCallback, useState } from 'react';
import { isEmpty, isSet } from '../utils/conditionals';
import { publish, subscribe } from '../utils/events';

/**
 * @param {"local","session","user","system"} type
 * @param {string} namespace
 * @param {string} key
 * @param {*} initial
 * @param {boolean} json
 * @returns {*,function,boolean}
 */
export default function useStorage( type = 'local', namespace = '', key = '', initial = null, json = false ) {
	const storage = 'session' === type ? sessionStorage : localStorage;
	const persistent = 'user' === type || 'system' === type;
	const setting = namespace ? namespace + '/' + key : key;

	const get = useCallback( ( fallback = null ) => {
		let value = storage.getItem( setting );
		if ( ! isSet( value ) ) {
			return fallback;
		}
		if ( ! json ) {
			return value;
		}
		try {
			return JSON.parse( value );
		} catch ( e ) {
			// @todo debug message?
		}
		return fallback;
	}, [ storage, setting, json ] );

	const set = useCallback( ( value ) => {
		if ( ! isSet( value ) ) {
			storage.removeItem( setting );
			return;
		}
		if ( json ) {
			value = JSON.stringify( value );
		}
		storage.setItem( setting, value );

		// @todo Call persistent storage.
	}, [ storage, setting, json ] );

	const update = ( key, value, json ) => {
		set( value );
		publish( 'update:' + type + 'Storage:' + setting, value );
	}

	subscribe( 'update:' + type + 'Storage:' + setting, () => {
		setValue( get() );
	} );

	const [ value, setValue ] = useState( get( initial ) );

	return [ value, update, ! isEmpty( storage.getItem( setting ) ) ];
}
