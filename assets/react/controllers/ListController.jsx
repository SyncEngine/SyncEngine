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
		query = { limit: 10, offset: 0 },
	} = args;

	const [ items, fetchItems, updateEntities, deleteEntity ] = useEntities( type, args.items, query );

	const editItem = ( entity ) => {
		updateEntities( entity );
	}

	const deleteItem = ( entity ) => {
		deleteEntity( entity );
	}

	const exportItem = ( entity ) => {
		// Nothing to do here.
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
