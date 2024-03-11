import { useCallback, useEffect } from 'react';
import { isEmpty, isPromise, isSet } from '../utils/conditions';
import useSyncedState from './useSyncedState';
import useGlobal from './useGlobal';
import { fetchPost } from '../utils/fetch';
import { objectMerge } from '../utils/data';

/**
 * @param {"local","session","user","system"} type
 * @param {string} namespace
 * @param {string} key
 * @param {*} initial
 * @param {boolean} json
 * @returns {*,function,boolean}
 */
export default function useSettings( type = 'local', namespace = '', key = '', initial = null, json = false ) {
	const app = useGlobal();
	const settings = 'session' === type ? sessionStorage : localStorage;
	const persistent = 'user' === type;// || 'system' === type;
	const setting = namespace ? namespace + '/' + key : key;

	useEffect( () => {
		if ( persistent ) {
			fetch( setting );
		}
	}, [] );

	const getGlobalKey = useCallback( ( type ) => {
		if ( 'user' === type ) {
			return 'preferences';
		}/* else if ( 'system' === type ) {
			return app.endpoints.system.settings;
		}*/
		return null;
	}, [] );

	const getEndpoint = useCallback( ( type ) => {
		if ( 'user' === type ) {
			return app.endpoints.user.preferences;
		}/* else if ( 'system' === type ) {
			return app.endpoints.system.settings;
		}*/
		return null;
	}, [] );

	const fetch = useCallback( async ( setting ) => {
		const endpoint = getEndpoint( type );
		const global = getGlobalKey( type );

		if ( isPromise( app[ global ] ) ) {
			// Already loading.
			const loaded = await app[ global ];

			return setting ? loaded[ setting ] : loaded;
		}

		if ( setting ) {
			if ( ! isEmpty( app[ global ][ setting ] ) ) {
				return app[ global ][ setting ];
			}
		} else if ( ! isEmpty( app[ global ] ) ) {
			return app[ global ];
		}

		const current = app[ global ];

		app[ global ] = fetchPost( endpoint, {} );

		const response = await app[ global ];

		app[ global ] = current;

		if ( response.success ) {
			app[ global ] = objectMerge( app[ global ], response.data );
		}

		return setting ? app[ global ][ setting ] : app[ global ];

	}, [ type ] );

	const persist = useCallback( async ( setting, value ) => {
		const endpoint = getEndpoint( type );
		const global   = getGlobalKey( type );
		const response = await fetchPost( endpoint, { action: 'update', setting: setting, value: value } );

		if ( response.success ) {
			app[ global ] = objectMerge( app[ global ], response.data );
		}

		return app[ global ][ setting ] ?? null;
	}, [ type ] );

	const get = useCallback( ( fallback = null ) => {
		let value = settings.getItem( setting );

		if ( persistent ) {
			if ( value !== app[ getGlobalKey( type ) ][ setting ] ) {
				value = app[ getGlobalKey( type ) ][ setting ];
				settings.setItem( setting, value );
			}
		}

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
	}, [ settings, setting, json ] );

	const set = useCallback( ( value ) => {
		if ( ! isSet( value ) ) {
			settings.removeItem( setting );
			return true;
		}
		if ( json ) {
			value = JSON.stringify( value );
		}
		settings.setItem( setting, value );

		if ( persistent ) {
			persist( setting, value );
		}
		return true;
	}, [ settings, setting, json, persistent, persist ] );

	const [ value, update ] = useSyncedState( 'update:' + type + 'Storage:' + setting, get( initial ), set, get );

	return [ value, update, ! isEmpty( settings.getItem( setting ) ) ];
}
