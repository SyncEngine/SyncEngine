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

	const [ items, fetchCallback, updateCallback, createCallback, deleteCallback ] = useEntities( type, args.items, query );

	const callbacks = {
		edit: updateCallback,
		create: createCallback,
		delete: deleteCallback,
		fetch: fetchCallback,
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
