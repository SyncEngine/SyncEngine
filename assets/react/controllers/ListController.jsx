import React, { useState, useEffect } from 'react';
import List from '../components/views/List';
import { fetchPost } from '../utils/fetch';
import { isEmpty } from '../utils/conditionals';
import useEntities from '../hooks/useEntities';

export default function ListController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		type,
		columns = {},
		query = {},
	} = args;

	const [ items, fetchItems ] = useEntities( type, args.items, { limit: 10, offset: 0 } );
	console.log( items );

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
