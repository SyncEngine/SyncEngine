import { useCallback, useEffect } from 'react';
import { isEmpty, isSet } from '../utils/conditions';
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
			fetch( getEndpoint( type ), getGlobal( type ), setting );
		}
	}, [] );

	const getGlobal = useCallback( ( type ) => {
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

	const fetch = useCallback( async ( endpoint, global, setting ) => {
		if ( setting ) {
			if ( ! isEmpty( app[ global ][ setting ] ) ) {
				return;
			}
		} else if ( ! isEmpty( app[ global ] ) ) {
			return;
		}

		const response = await fetchPost( endpoint, {} );

		if ( response.success ) {
			app[ global ] = objectMerge( app[ global ], response.data );
		}

	}, [] );

	const persist = useCallback( async ( endpoint, setting, value ) => {
		const response = await fetchPost( endpoint, { action: 'update', setting: setting, value: value } );

		if ( response.success ) {
			app.preferences = objectMerge( app.preferences, response.data );
		}

		return app.preferences[ setting ] ?? null;
	}, [] );

	const get = useCallback( ( fallback = null ) => {
		let value = settings.getItem( setting );

		if ( persistent ) {
			if ( value !== app[ getGlobal( type ) ][ setting ] ) {

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
			persist( getEndpoint( type ), setting, value );
		}
		return true;
	}, [ settings, setting, json, persistent ] );

	const [ value, update ] = useSyncedState( 'update:' + type + 'Storage:' + setting, get( initial ), set, get );

	return [ value, update, ! isEmpty( settings.getItem( setting ) ) ];
}
