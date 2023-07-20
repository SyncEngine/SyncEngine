import React from 'react';
import Actions from '../components/views/List/Cols/Actions';

export default function ActionsController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		actions = {},
		entity,
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
		<Actions
			actions={ actions }
			entity={ entity }
			type={ type }
			handleEdit={ editEntity }
			handleDelete={ deleteEntity }
			handleExport={ exportEntity }
		/>
	);
}
