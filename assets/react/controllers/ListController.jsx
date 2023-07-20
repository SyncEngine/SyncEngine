import React, { useState, useEffect } from 'react';
import List from '../components/views/List';
import { fetchPost } from '../utils/fetch';
import { isEmpty } from '../utils/conditionals';

export default function ListController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		type,
		columns = {},
		query = {},
		endpoint = window.app.endpoints.entities[ type ] ?? window.app.baseUrl,
	} = args;

	const [ items, setItems ] = useState( args.items );

	const fetchItems = async ( query = {} ) => {
		const response = await fetchPost( endpoint, { action: 'list', query: query } );

		if ( response.success ) {
			setItems( response.data );
		} else {
			setItems( null );
		}
	}

	useEffect( () => {
		if ( isEmpty( items ) ) {
			fetchItems();
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
			items={ items ?? Array( ( query.limit ?? 1 ) ).fill( 'placeholder' ) }
			type={ type }
		/>
	);
}
