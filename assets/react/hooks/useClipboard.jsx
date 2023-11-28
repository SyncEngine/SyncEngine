import useStorage from './useStorage';
import { useCallback, useEffect, useState } from 'react';
import { isSet } from '../utils/conditionals';
import { publish, subscribe } from '../utils/events';

/**
 * @param {string} key
 * @param {*} initial
 * @param {boolean} json
 * @returns {*,function,boolean}
 */
export default function useClipboard( key, initial = null, json = true ) {

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
				return;
			}
			if ( json ) {
				value = JSON.stringify( value );
			}
			navigator.clipboard.writeText( value );
		}, [ json ] );

		const update = ( value ) => {
			set( value );
			publish( 'update:Clipboard', value );
		}

		subscribe( 'update:Clipboard', async () => {
			setValue( await get() );
		} );

		const [ value, setValue ] = useState( initial );

		useEffect( () => {
			( async () => {
				setValue( await get( initial ) );
			} )();
		}, [] );

		return [ value, update, get ];
	}

	return useStorage( 'session', 'clipboard', key, initial, json );
}
