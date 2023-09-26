import React, { useState, useEffect } from 'react';
import useEntities from './useEntities';

/**
 *
 * @param {String} type
 * @param {int|string} id_or_ref
 * @param {Object[]} items
 * @param {Object} query
 * @param {String} endpoint
 * @return {[*,function,boolean]}
 */
export default function useEntity( type, id_or_ref = 0, items = [], query = null, endpoint = null ) {
	const [ entities, callbacks, loading ] = useEntities( type, items, query, endpoint );
	const [ current, setCurrent ] = useState( id_or_ref );

	useEffect( () => {
		if ( ! callbacks.get( current ) ) {
			getEntity( current );
		}
	}, [] );

	const getEntity = ( id_or_ref ) => {
		setCurrent( id_or_ref );

		if ( id_or_ref && ! callbacks.get( id_or_ref ) ) {
			callbacks.fetch( { id: id_or_ref } );
		}
	}

	return [ callbacks.get( current ), getEntity, loading ];
}
