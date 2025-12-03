import useSettings from './useSettings';
import { useCallback, useEffect } from 'react';
import { isEmpty, isScalar } from '../utils/conditions';
import useSyncedState from './useSyncedState';
import useGlobal from './useGlobal';

/**
 * @param {string} key
 * @param {*} initial
 * @param {boolean} json
 * @returns {*,function,boolean}
 */
export default function useClipboard( key, initial = '', json = true ) {
	const app = useGlobal();

	if ( ! key && ( ! navigator || ! navigator.clipboard ) ) {
		const alert = () => { alert( 'Clipboard not supported' ) };
		return [ null, alert, false ];
	}

	if ( navigator && navigator.clipboard ) {
		const get = async ( fallback ) => {
			if ( ! document.hasFocus() ) {
				return fallback;
			}
			let value = await navigator.clipboard.readText();
			if ( ! json ) {
				return value;
			}
			try {
				return JSON.parse( value );
			} catch ( e ) {
				if ( ! isEmpty( value ) ) {
					return value;
				}
			}
			return fallback;
		}

		const set = useCallback( ( value ) => {
			if ( isEmpty( value ) ) {
				return false; // Do not override with empty value.
			}
			if ( json && ! isScalar( value ) ) {
				value = JSON.stringify( value );
			}
			navigator.clipboard.writeText( value );
			return true;
		}, [ json ] );

		const [ value, update ] = useSyncedState( 'update:Clipboard', get( initial ), set, get );

		if ( ! app.clipboardListener ) {
			app.clipboardListener = async () => {
				update( await get( initial ), false, true );
			};

			// Reload clipboard on focus.
			window.addEventListener( 'focus', app.clipboardListener );
		}

		useEffect( () => {
			if ( document.hasFocus() ) {
				app.clipboardListener();
			}
		}, [] );

		return [ value, update, navigator.clipboard ];
	}

	return useSettings( 'session', 'clipboard', key, initial, json );
}
