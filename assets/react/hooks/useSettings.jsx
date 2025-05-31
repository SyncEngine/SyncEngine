import { useCallback, useEffect } from 'react';
import { isEmpty, isPromise, isValue } from '../utils/conditions';
import useSyncedState from './useSyncedState';
import useGlobal from './useGlobal';
import { fetchPost } from '../utils/fetch';
import { objectMerge } from '../utils/data';

function getTypeGlobal( type ) {
	if ( 'user' === type ) {
		return 'preferences';
	}/* else if ( 'system' === type ) {
			return app.endpoints.system.settings;
		}*/
	return null;
}

function getTypeEndpoint( type, app ) {
	if ( 'user' === type ) {
		return app.endpoints.user.preferences;
	}/* else if ( 'system' === type ) {
			return app.endpoints.system.settings;
		}*/
	return null;
}

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

	const get = useCallback( ( fallback = null ) => {
		let value = settings.getItem( setting );

		if ( persistent ) {
			if ( value !== app[ getTypeGlobal( type ) ][ setting ] ) {
				value = app[ getTypeGlobal( type ) ][ setting ];
				set( value );
			}
		}

		if ( ! isValue( value ) ) {
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
		if ( ! isValue( value ) ) {
			settings.removeItem( setting );
			return true;
		}
		if ( json ) {
			value = JSON.stringify( value );
		}
		settings.setItem( setting, value );

		return true;
	}, [ settings, setting, json ] );

	const [ value, update ] = useSyncedState( 'update:' + type + 'Storage:' + setting, get( initial ), set, get );

	const fetch = useCallback( async ( setting ) => {
		const endpoint = getTypeEndpoint( type, app );
		const global = getTypeGlobal( type );

		if ( isPromise( app[ global ] ) ) {
			// Already loading.
			const response = await app[ global ];

			if ( response.success ) {
				return setting ? response.data[ setting ] : response.data;
			}

			return undefined;
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

	const persist = useCallback( async ( value ) => {
		const endpoint = getTypeEndpoint( type, app );
		const global   = getTypeGlobal( type );
		const response = await fetchPost( endpoint, { action: 'update', setting: setting, value: value } );

		if ( response.success ) {
			app[ global ] = objectMerge( app[ global ], response.data );
			update( app[ global ][ setting ] ?? null );
		}

		return app[ global ][ setting ] ?? null;
	}, [ type, update, setting ] );

	useEffect( () => {
		if ( persistent ) {
			const fetchData = async () => {
				console.log( setting );
				update( await fetch( setting ) );
			}
			fetchData();
		}
	}, [ fetch, update ] );

	return [ value, persistent ? persist : update, ! isEmpty( settings.getItem( setting ) ) ];
}
