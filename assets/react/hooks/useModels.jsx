import React, { useState, useEffect } from 'react';
import { fetchPost } from '../utils/fetch';
import { isEmpty } from '../utils/conditionals';

/**
 * @param {Object} items
 * @param {Object} query
 * @param {String} endpoint
 * @returns {[Object,{fetch:((function((Object|Function)): Promise<void>)|*),update,total}]}
 */
export default function useModels( type, items = null, query = null, endpoint = null ) {
	const [ models, setModels ] = useState( items );
	let currentQuery = query;

	if ( ! endpoint ) {
		endpoint = window.app.endpoints.models[ type ];
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
		if ( isEmpty( query ) && ! isEmpty( window.app.models[ type ] ) ) {
			if ( updateState ) {
				setModels( window.app.models[ type ] );
			}
			return window.app.models[ type ];
		}

		query = setQuery( query );

		const results =
			await fetchPost(
				endpoint,
				{ action: 'query', query: query }
			);

		if ( results.success ) {
			if ( isEmpty( query ) ) {
				// Queried all models, so update global.
				update( results.data );
			}

			if ( updateState ) {
				setModels( results.data ?? {} );
			}

			return results.data;
		}

		if ( updateState ) {
			setModels( {} );
		}
		return results.error ?? null;
	}

	/**
	 * @param {string} name
	 * @param {boolean|object}query
	 * @returns {object|null}
	 */
	const get = async ( name, query = {} ) => {
		if ( isEmpty( models ) && query ) {
			const fetched = await fetch( query );
			return fetched[ name ] ?? null;
		}

		if ( ! models ) {
			return {};
		}

		return models[ name ] ?? null;
	}

	/**
	 * @param {Object[]|Object} models
	 */
	const update = ( models ) => {
		window.app.models[ type ] = models;
	}

	/**
	 * @returns {number}
	 */
	const getTotal = () => {
		return Object.keys( window.app.models[ type ] ).length;
	}

	const callbacks = {
		fetch: fetch,
		update: update,
		get: get,
		getTotal: getTotal,
		getQuery: getQuery,
	}

	return [ models, callbacks ];
}
