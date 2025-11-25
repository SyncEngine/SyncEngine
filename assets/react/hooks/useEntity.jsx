import { useEffect, useState } from 'react';
import useEntities from './useEntities';

/**
 * @param {String} type
 * @param {int|string} id_or_ref
 * @param {Object[]} items
 * @param {Object} query
 * @param {String} endpoint
 * @return {[*|Object|null,{add, total, search: ((function(*): Promise<null|*>)|*), set: function, edit, fetch: ((function((Object|Function)): Promise<void>)|*), get, update, remove},boolean]}
 */
export default function useEntity( type, id_or_ref = 0, items = [], query = null, endpoint = null ) {
	const [ entities, callbacks, loading ] = useEntities( type, items, query, endpoint );
	const [ current, setCurrent ] = useState( id_or_ref );

	useEffect( () => {
		if ( ! callbacks.get( current, true ) ) {
			fetchEntity( current );
		}
	}, [] );

	const fetchEntity = async ( id_or_ref ) => {
		if ( ! id_or_ref ) {
			return null;
		}

		if ( isNaN( id_or_ref ) ) {
			await callbacks.fetch( { search: { ref: id_or_ref } } );
		} else {
			await callbacks.fetch( { where: { id: id_or_ref } } );
		}

		return callbacks.get( id_or_ref, true );
	}

	const searchEntity = async ( id_or_ref ) => {
		if ( ! id_or_ref ) {
			return null;
		}

		const entity = callbacks.get( id_or_ref, true );

		// Trigger query to the return value can properly get the entity.
		if ( ! entity ) {
			return await fetchEntity( id_or_ref );
		}

		return entity;
	}

	const setEntity = ( id_or_ref, search = true ) => {
		setCurrent( id_or_ref );

		if ( ! id_or_ref ) {
			return;
		}

		if ( search ) {
			searchEntity( id_or_ref );
		}
	}

	return [ callbacks.get( current, true ) ?? null, { ...callbacks, search: searchEntity, set: setEntity }, loading ];
}
