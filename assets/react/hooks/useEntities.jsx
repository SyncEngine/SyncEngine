import React, { useState, useEffect } from 'react';
import { fetchPost } from '../utils/fetch';
import { isEmpty } from '../utils/conditionals';

/**
 * @param {String} type
 * @param {Object[]} items
 * @param {Object} query
 * @param {String} endpoint
 * @returns {[Object[],((function((Object|Function)): Promise<void>)|*),update,add,remove]}
 */
export default function useEntities( type, items = null, query = null, endpoint = null ) {
	const [ entities, setEntities ] = useState( items );
	let currentQuery = query;

	if ( ! endpoint ) {
		endpoint = window.app.endpoints.entities[ type ] ?? window.app.baseUrl;
	}

	useEffect(() => {
		if ( ! isEmpty( items ) ) {
			update( items );
		} else if ( query ) {
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
	 * @param {Object|CallableFunction} query
	 * @param {Boolean} updateState
	 * @returns {Promise<void>}
	 */
	const fetch = async ( query, updateState = true ) => {
		query = setQuery( query );

		const results =
			await fetchPost(
				endpoint,
				{ action: 'query', query: query }
			);

		if ( results.success ) {
			update( results.data );
			if ( updateState ) {
				setEntities( results.data ?? [] );
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
		if ( ! Array.isArray( entities ) && entities.hasOwnProperty( 'id' ) ) {
			entities = [ entities ];
		}

		entities.forEach( ( entity ) => {
			if ( ! window.app.entities[ type ] ) {
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

	return [ entities, fetch, update, add, remove ];
}
