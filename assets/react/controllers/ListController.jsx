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

	return (
		<List
			actions={ actions }
			columns={ columns }
			items={ items }
			type={ type }
			handleEdit={ editEntity }
			handleDelete={ deleteEntity }
			handleExport={ exportEntity }
		/>
	);
}
