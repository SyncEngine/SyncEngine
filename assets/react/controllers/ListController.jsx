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

	const [ items, fetch, update, add, remove ] = useEntities( type, args.items, query );

	const exportItem = ( entity ) => {
		// Nothing to do here.
	}

	const callbacks = {
		edit: update,
		create: add,
		delete: remove,
		export: exportItem,
		fetch: fetch,
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
