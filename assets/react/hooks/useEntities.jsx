import React, { useState, useEffect } from 'react';
import { fetchPost } from '../utils/fetch';
import { isEmpty } from '../utils/conditionals';

/**
 * @param {String} type
 * @param {Object[]} items
 * @param {Object} query
 * @param {String} endpoint
 * @returns {[Object[],{fetch:((function((Object|Function)): Promise<void>)|*),update,add,remove,total}]}
 */
export default function useEntities( type, items = null, query = null, endpoint = null ) {
	const [ entities, setEntities ] = useState( items );
	const [ currentQuery, setQuery ] = useState( query );

	if ( ! endpoint ) {
		endpoint = window.app.endpoints.entities[ type ] ?? window.app.baseUrl;
	}

	useEffect(() => {
		if ( ! isEmpty( items ) ) {
			update( items );
		} else if ( currentQuery ) {
			fetch( currentQuery );
		}
	}, [] );

	/**
	 * @param {Object|CallableFunction} query
	 * @returns {Object}
	 */
	const updateQuery = ( query ) => {
		let newQuery = currentQuery;
		if ( 'function' === typeof query ) {
			newQuery = query( newQuery );
		} else {
			newQuery = query;
		}

		// Parse numeric values.
		for ( const key in newQuery ) {
			if ( ! newQuery.hasOwnProperty( key ) ) {
				continue;
			}
			if ( 'string' === typeof newQuery[ key ] && ! isNaN( newQuery[ key ] ) ) {
				newQuery[ key ] = parseInt( newQuery[ key ], 10 );
			}
		}

		setQuery( newQuery );
		return newQuery;
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
	 * @param {Boolean|String} updateState
	 * @returns {Promise<void>}
	 */
	const fetch = async ( query, updateState = true ) => {
		query = updateQuery( query );

		const results =
			await fetchPost(
				endpoint,
				{ action: 'query', query: query }
			);

		if ( results.success ) {
			update( results.data );

			if ( updateState ) {
				if ( results.hasOwnProperty( 'total' ) ) {
					updateTotal( results.total );
				}
				if ( 'append' === updateState ) {
					setEntities( [ ...entities, ...results.data ] );
				} else {
					setEntities( results.data ?? [] );
				}
			}

			return results.data;
		}

		if ( updateState ) {
			setEntities( null );
		}
		return results.error ?? null;
	}

	/**
	 * @param {Object[]|Object} entities
	 */
	const update = ( entities ) => {
		if ( ! Array.isArray( entities ) ) {
			if ( ! entities.hasOwnProperty( 'id' ) ) {
				return; // @todo error.
			}
			entities = [ entities ];
		}

		// @todo Entity manager hook?
		entities.forEach( ( entity ) => {
			if ( 'object' !== typeof window.app.entities[ type ] ) {
				window.app.entities[ type ] = {};
			}
			if ( ! window.app.entities[ type ].hasOwnProperty( entity.id ) ) {
				window.app.entities[ type ][ entity.id ] = entity;
			} else {
				// Update global without removing reference.
				for ( const field in entity ) {
					if ( entity.hasOwnProperty( field ) ) {
						window.app.entities[ type ][ entity.id ][ field ] = entity[ field ];
					}
				}
			}
		} );
	}

	/**
	 * @param {number} total
	 */
	const updateTotal = ( total ) => {
		if ( ! window.app.entities[ type ] ) {
			window.app.entities[ type ] = {};
		}
		window.app.entities[ type ].total = total;
	}

	/**
	 * @returns {number}
	 */
	const getTotal = () => {
		return window.app.entities.hasOwnProperty( type ) ? window.app.entities[ type ].total ?? 0 : 0;
	}

	/**
	 * @param {Object} entity
	 */
	const add = ( entity ) => {
		if ( ! entity.hasOwnProperty( 'id' ) ) {
			return;
		}
		setEntities( entities.unshift( entity ) );

		update( entity );
	}

	/**
	 * @param {Object|int} entityId
	 */
	const remove = ( entityId ) => {
		if ( isNaN( entityId ) ) {
			if ( ! entityId.hasOwnProperty( 'id' ) ) {
				return;
			}
			entityId = entityId.id;
		}
		setEntities( entities.filter( ( item => item.id !== entityId ) ) );

		if ( ! window.app.entities[ type ] ) {
			return;
		}
		delete window.app.entities[ type ][ entityId ];
	}

	const callbacks = {
		fetch: fetch,
		update: update,
		add: add,
		remove: remove,
		getTotal: getTotal,
		getQuery: getQuery,
	}

	return [ entities, callbacks ];
}
