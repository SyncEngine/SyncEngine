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
			setEntities( results.data ?? [] );
			return;
		}
		setEntities( null );
	}

	return [ entities, fetchEntities ];
}
