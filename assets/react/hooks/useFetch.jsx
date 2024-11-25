import { useEffect, useState } from 'react';
import useGlobal from './useGlobal';
import useSyncedState from './useSyncedState';
import { fetchPost } from '../utils/fetch';
import { fromFormat, toFormat } from '../utils/format';
import { isString } from '../utils/conditions';

function parseEndpoint( endpoint, resource ) {
	const parts = endpoint.split( '.' );

	let parsed = resource;
	for ( const i in parts ) {
		if ( parsed[ parts[i] ] ) {
			parsed = parsed[ parts[i] ];
		} else {
			return null;
		}
	}

	return isString( parsed ) && parsed;
}

export default function useFetch( endpoint, query = null ) {
	const app = useGlobal();

	const url = parseEndpoint( endpoint, app.endpoints );

	if ( ! url ) {
		return [];
	}

	let params = '';
	if ( isString( query ) ) {
		params = query;
		query = fromFormat( query, 'url' );
	} else {
		params = toFormat( query, 'url' );
	}

	const [ result, setResult ] = useSyncedState( 'fetch:' + endpoint + ':' + params, null );
	const [ loading, setLoading ] = useState( false );

	useEffect(() => {
		if ( null === result ) {
			fetch( true );
		}
	}, [] );

	/**
	 * @param {Object|CallableFunction} query
	 * @param {Boolean|String} updateState
	 * @returns {Promise<Array|boolean|string>}
	 */
	const fetch = async ( updateState = true ) => {
		setLoading( ( updateState && 'silent' !== updateState ) );

		const results = await fetchPost( url, query );

		if ( results.success ) {
			if ( updateState ) {
				setResult( results.data );
			}

			setLoading( false );
			return true;
		}

		if ( updateState ) {
			setResult( null );
		}

		setLoading( false );
		return results.error ?? false;
	}

	const reload = () => {
		return fetch( true );
	}

	return [ result, reload, loading ];
}
