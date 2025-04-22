import { useEffect, useState } from 'react';
import useGlobal from './useGlobal';
import { fetchPost } from '../utils/fetch';
import { isEmpty, validate } from '../utils/conditions';
import { deepClone } from '../utils/data';

const fetching = {};

/**
 * @param {Object} items
 * @param {Object} query
 * @param {String} endpoint
 * @returns {[Object,{fetch:((function((Object|Function)): Promise<void>)|*),update,total}]}
 */
export default function useModels( type, items = null, query = null, endpoint = null ) {
	const app = useGlobal();
	const [ models, setModels ] = useState( items );
	const [ loading, setLoading ] = useState( false );

	let currentQuery =  'object' === typeof query ? query : {};

	if ( ! endpoint ) {
		endpoint = app.endpoints.models[ type ];
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
		} else if ( 'object' === typeof query ) {
			currentQuery = query;
		} else {
			currentQuery = {};
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
	const fetch = async ( query = {}, updateState = true ) => {
		setLoading( ( updateState && 'silent' !== updateState ) );
		query = setQuery( query );

		const filterQuery = query.filter ?? null;
		delete query.filter;

		if ( isEmpty( query ) && ! isEmpty( app.models[ type ] ) ) {
			const result = ( filterQuery ) ? filter( filterQuery, true ) : app.models[ type ];
			if ( updateState ) {
				setModels( result );
			}
			setLoading( false );
			return result;
		}

		if ( ! fetching.hasOwnProperty( endpoint ) ) {
			fetching[ endpoint ] =
				fetchPost(
					endpoint,
					{ action: 'query', query: query }
				);
		}

		const results = await fetching[ endpoint ];
		delete fetching[ endpoint ];

		if ( results.success ) {

			let result = results.data ?? {};

			if ( isEmpty( query ) ) {
				// Queried all models, so update global.
				update( result );
			}

			if ( filterQuery ) {
				result = filter( filterQuery, result );
			}

			if ( updateState ) {
				setModels( result );
			}

			setLoading( false );
			return results;
		}

		if ( updateState ) {
			setModels( {} );
		}
		setLoading( false );
		return results.error ?? null;
	}

	/**
	 * @param {object} conditions
	 * @param {object|boolean} models
	 * @return {object}
	 */
	const filter = ( conditions, models = true ) => {
		let filteredModels = deepClone( ( true === models ) ? app.models[ type ] : models );

		for ( const name in filteredModels ) {
			if ( ! filteredModels.hasOwnProperty( name ) ) {
				continue;
			}

			if ( ! validate( conditions, filteredModels[ name ] ) ) {
				delete filteredModels[ name ];
			}
		}

		return filteredModels;
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
		app.models[ type ] = models;
	}

	/**
	 * @returns {number}
	 */
	const getTotal = () => {
		return Object.keys( app.models[ type ] ).length;
	}

	const callbacks = {
		fetch: fetch,
		filter: filter,
		update: update,
		get: get,
		getTotal: getTotal,
		getQuery: getQuery,
	}

	return [ models, callbacks, loading ];
}
