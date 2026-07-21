import React from 'react';
import PropTypes from 'prop-types';
import Actions from '../components/views/Blocks/Actions';

export default function ActionsController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		actions = [],
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
			callbacks={ {
				edit: editEntity,
				delete: deleteEntity,
				export: exportEntity,
			} }
			entity={ entity }
			type={ type }
		/>
	);
}

ActionsController.propTypes = {
	args: PropTypes.object,
	onChange: PropTypes.func,
};
