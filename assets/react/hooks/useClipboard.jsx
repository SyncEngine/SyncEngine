import useStorage from './useStorage';
import { useCallback, useEffect } from 'react';
import { isSet } from '../utils/conditionals';
import useSyncedState from './useSyncedState';

/**
 * @param {string} key
 * @param {*} initial
 * @param {boolean} json
 * @returns {*,function,boolean}
 */
export default function useClipboard( key, initial = '', json = true ) {
	if ( ! key && ! navigator || ! navigator.clipboard ) {
		const alert = () => { alert( 'Clipboard not supported' ) };
		return [ null, alert, alert ];
	}

	if ( navigator && navigator.clipboard ) {
		const get = async ( fallback ) => {
			let value = await navigator.clipboard.readText();
			if ( ! json ) {
				return value;
			}
			try {
				return JSON.parse( value );
			} catch ( e ) {
				// @todo debug message?
			}
			return fallback;
		}

		const set = useCallback( ( value ) => {
			if ( ! isSet( value ) ) {
				return false;
			}
			if ( json ) {
				value = JSON.stringify( value );
			}
			navigator.clipboard.writeText( value );
			return true;
		}, [ json ] );

		const [ value, update ] = useSyncedState( 'update:Clipboard', initial, set, get );

		useEffect( () => {
			( async () => {
				update( await get( initial ) );
			} )();
		}, [] );

		return [ value, update, get ];
	}

	return useStorage( 'session', 'clipboard', key, initial, json );
}
