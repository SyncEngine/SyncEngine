import React, { useState, useEffect } from 'react';
import { fetchPost } from '../utils/fetch';
import { isEmpty, validate } from '../utils/conditions';
import useGlobal from './useGlobal';
import useSyncedState from './useSyncedState';

export default function useSecrets() {
	const app = useGlobal();
	const [ secrets, setSecrets ] = useSyncedState( 'update:Secrets', app.secrets ?? {} );
	const [ loading, setLoading ] = useState( false );

	const endpoints = app.endpoints.secrets;

	useEffect( () => {
		if ( isEmpty( secrets ) ) {
			fetch();
		}
	}, [] );

	const query = async ( endpoint, data, updateState = false ) => {
		const results =
			await fetchPost( endpoint, data );

		if ( results.success ) {

			let result = results.data ?? {};

			if ( updateState ) {
				app.secrets = result;
				setSecrets( result );
			}
			setLoading( false );
			return results;
		}

		setLoading( false );
		return results.error ?? null;
	}

	/**
	 * @returns {Promise<void>}
	 */
	const fetch = async () => {
		return query( endpoints.list, {}, true );
	}

	const reveal = async () => {
		return query( endpoints.export, {}, false );
	}

	const add = async ( key, value ) => {
		return query( endpoints.set, { key: key, value: value }, true );
	}

	const remove = async ( key ) => {
		return query( endpoints.unset, { key: key }, true );
	}

	const callbacks = {
		fetch: fetch,
		add: add,
		remove: remove,
		reveal: reveal,
	}

	return [ secrets, callbacks, loading ];
}
