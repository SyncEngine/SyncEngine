import React, { useState, useEffect } from 'react';
import { fetchPost } from '../utils/fetch';
import { isEmpty } from '../utils/conditionals';
import { mapGetIndex } from '../utils/data';

/**
 * @param {String} type
 * @param {Object[]} items
 * @param {Object} query
 * @param {String} endpoint
 * @returns {[Object[],{fetch:((function((Object|Function)): Promise<void>)|*),update,add,remove,total}]}
 */
export default function useEntities( type, items = [], query = null, endpoint = null ) {
	const [ entities, setEntities ] = useState( items );
	const [ currentQuery, setQuery ] = useState( query );
	const [ loading, setLoading ] = useState( false );

	if ( ! endpoint ) {
		endpoint = window.app.endpoints.entities[ type ] ?? window.app.baseUrl;
	}

	useEffect(() => {
		if ( ! isEmpty( items ) ) {
			update( items, false );
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
		setLoading( true );
		query = updateQuery( query );

		const results =
			await fetchPost(
				endpoint,
				{ action: 'query', query: query }
			);

		if ( results.success ) {
			update( results.data, false );

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

			setLoading( false );
			return results.data;
		}

		if ( updateState ) {
			setEntities( null );
		}
		setLoading( false );
		return results.error ?? null;
	}

	/**
	 * @param {Object[]|Object} entities
	 * @param {Boolean} updateState
	 */
	const update = ( entities, updateState = true ) => {
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

		if ( updateState ) {
			setEntities( entities );
		}
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
	 * @returns {number|null}
	 */
	const getTotal = () => {
		return window.app.entities.hasOwnProperty( type ) ? window.app.entities[ type ].total ?? null : null;
	}

	/**
	 * @param {Object} entity
	 * @param {Boolean} updateState
	 */
	const edit = ( entity, updateState = true ) => {
		if ( Array.isArray( entity ) ) {
			// Bulk edit.
			update( entity, updateState );
			return;
		}
		update( entity, false );

		setEntities( entities.map( item => {
			if ( item.id === entity.id ) {
				return entity;
			}
			return item;
		} ) );
	}

	/**
	 * @param {Object} entity
	 */
	const add = ( entity ) => {
		if ( ! entity.hasOwnProperty( 'id' ) ) {
			return;
		}
		entities.push( entity );
		setEntities( [ ...entities ] );

		update( entity, false );
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
		setEntities( [ ...entities.filter( ( item => item.id !== entityId ) ) ] );

		if ( ! window.app.entities[ type ] ) {
			return;
		}
		delete window.app.entities[ type ][ entityId ];
	}

	const get = ( id_or_ref, global = false ) => {
		let items = entities;
		if ( global && window.app.entities[ type ] ) {
			items = Object.values( window.app.entities[ type ] );
		}

		if ( isNaN( id_or_ref ) ) {
			return items[ mapGetIndex( items, id_or_ref, 'ref' ) ] ?? null;
		}
		return items[ mapGetIndex( items, id_or_ref, 'id' ) ] ?? null;
	}

	const callbacks = {
		get: get,
		fetch: fetch,
		update: update,
		add: add,
		edit: edit,
		remove: remove,
		getTotal: getTotal,
		getQuery: getQuery,
	}

	return [ entities, callbacks, loading ];
}
