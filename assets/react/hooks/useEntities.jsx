import { useEffect, useState } from 'react';
import useGlobal from './useGlobal';
import { fetchPost } from '../utils/fetch';
import { isEmpty, validate } from '../utils/conditions';
import { deepClone, mapGetIndex, objectMergeDepth } from '../utils/data';
import { isTag } from '../utils/tags';

/**
 * @param {String} type
 * @param {Object[]} items
 * @param {Object} query
 * @param {String} endpoint
 * @returns {[Object[],{fetch:((function((Object|Function)): Promise<void>)|*),update:function,add:function,edit:function,remove:function,get:function,getTotal:function,getQuery:function},boolean]}
 */
export default function useEntities( type, items = [], query = null, endpoint = null ) {
	const app = useGlobal();
	const [ entities, setEntities ] = useState( items );
	const [ currentQuery, setQuery ] = useState( query );
	const [ loading, setLoading ] = useState( false );

	if ( ! endpoint ) {
		endpoint = app.endpoints.entities[ type ] ?? app.baseUrl;
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
	 * @param {Boolean} updateState
	 * @returns {Object}
	 */
	const updateQuery = ( query, updateState = true ) => {
		let newQuery = updateState ? currentQuery : deepClone( currentQuery );
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

		if ( updateState ) {
			setQuery( newQuery );
		}
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
	 * @returns {Promise<Array|false|string>}
	 */
	const fetch = async ( query, updateState = true ) => {
		setLoading( ( updateState && 'silent' !== updateState ) );
		query = updateQuery( query, 'append' !== updateState );

		const filterQuery   = query.filter ?? null;
		const resultFilters = {};

		for ( const key in filterQuery ) {
			if ( isTag( key, false ) ) {
				// @todo Server side filters?
				resultFilters[ key ] = filterQuery[ key ];
			}
		}

		const results =
			await fetchPost(
				endpoint,
				{ action: 'query', query: query }
			);

		if ( results.success ) {

			let entityRefs = update( results.data, false );

			if ( updateState ) {
				if ( isEmpty( resultFilters ) && results.hasOwnProperty( 'total' ) ) {
					updateTotal( results.total );
				}
				if ( 'append' === updateState ) {
					entityRefs = [ ...entities, ...entityRefs ];
				}
				if ( ! isEmpty( resultFilters ) ) {
					entityRefs = filter( resultFilters, entityRefs );
				}

				setEntities( entityRefs );
			}

			setLoading( false );
			return entityRefs;
		}

		if ( updateState ) {
			setEntities( null );
		}
		setLoading( false );
		return results.error ?? false;
	}

	/**
	 * @param {object} conditions
	 * @param {object|boolean} entities
	 * @return {object}
	 */
	const filter = ( conditions, entities = true ) => {
		let filtered = deepClone( ( true === entities ) ? app.entities[ type ] : entities );

		for ( const name in filtered ) {
			if ( ! filtered.hasOwnProperty( name ) ) {
				continue;
			}

			if ( ! validate( conditions, filtered[ name ] ) ) {
				delete filtered[ name ];
			}
		}

		return filtered;
	}

	/**
	 * @param {Object[]|Object} entities
	 * @param {Boolean} updateState
	 * @return {Array|false}
	 */
	const update = ( entities, updateState = true ) => {
		if ( ! Array.isArray( entities ) ) {
			if ( ! entities.hasOwnProperty( 'id' ) ) {
				return false; // @todo error.
			}
			entities = [ entities ];
		}

		const entityRefs = [];

		// @todo Entity manager hook?
		entities.forEach( ( entity ) => {
			if ( 'object' !== typeof app.entities[ type ] ) {
				app.entities[ type ] = {};
			}
			if ( ! app.entities[ type ].hasOwnProperty( entity.id ) ) {
				app.entities[ type ][ entity.id ] = entity;
			} else {
				// Update global without removing reference.
				app.entities[ type ][ entity.id ] = objectMergeDepth(
					app.entities[ type ][ entity.id ],
					1,
					entity
				)
			}

			entityRefs.push( app.entities[ type ][ entity.id ] );
		} );

		if ( updateState ) {
			setEntities( entityRefs );
		}

		return entityRefs;
	}

	/**
	 * @param {number} total
	 */
	const updateTotal = ( total ) => {
		if ( ! app.entities[ type ] ) {
			app.entities[ type ] = {};
		}
		app.entities[ type ].total = total;
	}

	/**
	 * @returns {number|null}
	 */
	const getTotal = () => {
		return app.entities.hasOwnProperty( type ) ? app.entities[ type ].total ?? null : null;
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
		// Edit registered entity.
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

		// Register new entity.
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

		if ( ! app.entities[ type ] ) {
			return;
		}
		delete app.entities[ type ][ entityId ];
	}

	const deleteAndReload = async ( entityId, updateState = true ) => {
		if ( isNaN( entityId ) ) {
			if ( ! entityId.hasOwnProperty( 'id' ) ) {
				return;
			}
			entityId = entityId.id;
		}
		setLoading( ( updateState && 'silent' !== updateState ) );

		const results =
			await fetchPost(
				endpoint,
				{ action: 'delete|query', delete: entityId, query: query }
			);

		setLoading( false );
		if ( ! results.success ) {
			return results.error ?? false;
		}

		if ( updateState ) {
			remove( entityId );
			update( results.data, true );
		}

		return true;
	}

	const get = ( id_or_ref, global = false ) => {
		let items = entities;
		if ( global && app.entities[ type ] ) {
			items = Object.values( app.entities[ type ] );
		}

		if ( isNaN( id_or_ref ) ) {
			return items[ mapGetIndex( items, id_or_ref, 'ref' ) ] ?? null;
		}
		return items[ mapGetIndex( items, parseInt( id_or_ref, 10 ), 'id' ) ] ?? null;
	}

	const callbacks = {
		get: get,
		fetch: fetch,
		update: update,
		add: add,
		edit: edit,
		remove: remove,
		delete: deleteAndReload,
		getTotal: getTotal,
		getQuery: getQuery,
	}

	return [ entities, callbacks, loading ];
}
