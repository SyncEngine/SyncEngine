import React, { useState, useEffect } from 'react';
import { fetchPost } from '../utils/fetch';
import { isEmpty } from '../utils/conditionals';

/**
 * @param {Object} items
 * @param {Object} query
 * @param {String} endpoint
 * @returns {[Object,{fetch:((function((Object|Function)): Promise<void>)|*),update,total}]}
 */
export default function useTasks( items = null, query = null, endpoint = null ) {
	const [ tasks, setTasks ] = useState( items );
	let currentQuery = query;

	if ( ! endpoint ) {
		endpoint = window.app.endpoints.types.tasks;
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
		if ( isEmpty( query ) && ! isEmpty( window.app.types.tasks ) ) {
			if ( updateState ) {
				setTasks( window.app.types.tasks );
			}
			return window.app.types.tasks;
		}

		query = setQuery( query );

		const results =
			await fetchPost(
				endpoint,
				{ action: 'query', query: query }
			);

		if ( results.success ) {
			if ( isEmpty( query ) ) {
				// Queried all tasks, so update global.
				update( results.data );
			}

			if ( updateState ) {
				setTasks( results.data ?? {} );
			}

			return results.data;
		}

		if ( updateState ) {
			setTasks( {} );
		}
		return results.error ?? null;
	}

	/**
	 * @param {Object[]|Object} tasks
	 */
	const update = ( tasks ) => {
		window.app.types.tasks = tasks;
	}

	/**
	 * @returns {number}
	 */
	const getTotal = () => {
		return Object.keys( window.app.types.tasks ).length;
	}

	const callbacks = {
		fetch: fetch,
		update: update,
		getTotal: getTotal,
		getQuery: getQuery,
	}

	return [ tasks, callbacks ];
}
