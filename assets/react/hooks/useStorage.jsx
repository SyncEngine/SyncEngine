import { useCallback } from 'react';
import { isEmpty, isSet } from '../utils/conditions';
import useSyncedState from './useSyncedState';

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
			return true;
		}
		if ( json ) {
			value = JSON.stringify( value );
		}
		storage.setItem( setting, value );
		return true;
		// @todo Call persistent storage.
	}, [ storage, setting, json ] );

	const [ value, update ] = useSyncedState( 'update:' + type + 'Storage:' + setting, get( initial ), set, get );

	return [ value, update, ! isEmpty( storage.getItem( setting ) ) ];
}
