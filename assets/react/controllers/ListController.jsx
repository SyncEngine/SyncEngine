import React from 'react';
import List from '../components/views/List';
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

	const [ items, fetchItems, updateEntities ] = useEntities( type, args.items, { limit: 10, offset: 0 } );

	const editItem = ( entity ) => {
		updateEntities( entity );
	}

	const deleteItem = ( entity ) => {
		console.log( 'add' );
	}

	const exportItem = ( entity ) => {
		console.log( 'export' );
	}

	const callbacks = {
		edit: editItem,
		delete: deleteItem,
		export: exportItem,
		fetch: fetchItems,
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
