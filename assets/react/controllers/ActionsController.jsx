import React from 'react';
import ListActions from '../components/views/List/ListActions';

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
		<ListActions actions={ actions } entity={ entity } type={ type } handleEdit={ editEntity } handleDelete={ deleteEntity } handleExport={ exportEntity } />
	);
}
