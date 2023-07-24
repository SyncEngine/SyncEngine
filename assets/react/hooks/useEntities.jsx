import React, { useState, useEffect } from 'react';
import { fetchPost } from '../utils/fetch';
import { isEmpty } from '../utils/conditionals';

export default function useEntities( type, items, query, endpoint ) {
	const [ entities, setEntities ] = useState( items );

	if ( ! endpoint ) {
		endpoint = window.app.endpoints.entities[ type ] ?? window.app.baseUrl;
	}

	useEffect(() => {
		if ( isEmpty( items ) && query ) {
			fetchEntities( query );
		}
	}, [] );

	const fetchEntities = async ( query ) => {
		const results =
			await fetchPost(
				endpoint,
				{ action: 'query', query: query }
			);

		if ( results.success ) {
			updateEntities( results.data );
			setEntities( results.data ?? [] );
			return;
		}
		setEntities( null );
	}

	const updateEntities = ( entities ) => {
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

	return [ entities, fetchEntities, updateEntities ];
}
