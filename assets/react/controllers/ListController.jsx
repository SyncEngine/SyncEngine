import React from 'react';
import List from '../components/views/List';

export default function ListController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		actions = {},
		columns = {},
		items = {},
		type,
	} = args;

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
