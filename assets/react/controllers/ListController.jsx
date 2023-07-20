import React, { useState, useEffect } from 'react';
import List from '../components/views/List';
import { fetchPost } from '../utils/fetch';

export default function ListController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		columns = {},
		type,
		endpoint = window.app.endpoints.entities[ type ] ?? window.app.baseUrl,
	} = args;

	const [ items, setItems ] = useState( args.items ?? {} );

	const fetchItems = async ( query = {} ) => {
		const response = await fetchPost( endpoint, { action: 'list', query: query } );

		if ( response.success ) {
			return response[ type ] ?? response.data ?? [];
		}
		// @todo Error.
		return [];
	}

	useEffect( async() => {
		if ( ! items ) {
			const result = await fetchItems();
			setItems( result );
		}
	}, [] );

	const editEntity = ( entity ) => {
		console.log( 'edit' );
	}

	const deleteEntity = ( entity ) => {
		console.log( 'add' );
	}

	const exportEntity = ( entity ) => {
		console.log( 'export' );
	}

	const callbacks = {
		edit: editEntity,
		delete: deleteEntity,
		export: exportEntity,
	};

	return (
		<List
			callbacks={ callbacks }
			columns={ columns }
			items={ items }
			type={ type }
		/>
	);
}
