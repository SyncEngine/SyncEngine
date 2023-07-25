import React, { useState, useEffect } from 'react';
import { fetchPost } from '../utils/fetch';
import { isEmpty } from '../utils/conditionals';

/**
 * @param {Object} items
 * @param {Object} query
 * @param {String} endpoint
 * @returns {[Object,{fetch:((function((Object|Function)): Promise<void>)|*),update,total}]}
 */
export default function useWebservices( items = null, query = null, endpoint = null ) {
	const [ webservices, setWebservices ] = useState( items );
	let currentQuery = query;

	if ( ! endpoint ) {
		endpoint = window.app.endpoints.types.webservices;
	}

	useEffect(() => {
		if ( isEmpty( items ) && query ) {
			fetch( query );
		}
	}, [] );

	/**
	 * @param {Object|CallableFunction} query
	 * @returns {Object}
	 */
	const setQuery = ( query ) => {
		if ( 'function' === typeof query ) {
			currentQuery = query( currentQuery );
		} else {
			currentQuery = query;
		}
		return currentQuery;
	}

	/**
	 * @param {String} param
	 * @returns {Object|*|null}
	 */
	const getQuery = ( param = null ) => {
		if ( param ) {
			return currentQuery.hasOwnProperty( param ) ? currentQuery[ param ] : null;
		}
		return currentQuery;
	}

	/**
	 * @param {Object|CallableFunction} query
	 * @param {Boolean} updateState
	 * @returns {Promise<void>}
	 */
	const fetch = async ( query, updateState = true ) => {
		if ( isEmpty( query ) && ! isEmpty( window.app.types.webservices ) ) {
			if ( updateState ) {
				setWebservices( window.app.types.webservices );
			}
			return window.app.types.webservices;
		}

		query = setQuery( query );

		const results =
			await fetchPost(
				endpoint,
				{ action: 'query', query: query }
			);

		if ( results.success ) {
			if ( isEmpty( query ) ) {
				// Queried all webservices, so update global.
				update( results.data );
			}

			if ( updateState ) {
				setWebservices( results.data ?? {} );
			}

			return results.data;
		}

		if ( updateState ) {
			setWebservices( {} );
		}
		return results.error ?? null;
	}

	/**
	 * @param {Object[]|Object} webservices
	 */
	const update = ( webservices ) => {
		window.app.types.webservices = webservices;
	}

	/**
	 * @returns {number}
	 */
	const getTotal = () => {
		return Object.keys( window.app.types.webservices ).length;
	}

	const callbacks = {
		fetch: fetch,
		update: update,
		getTotal: getTotal,
		getQuery: getQuery,
	}

	return [ webservices, callbacks ];
}
