import React from 'react';
import ConfirmModal from '../ConfirmModal';

export default function DeleteModal( props ) {
	const {
		text = 'Are you sure?',
		confirm = 'Delete',
		children = <span className="bi bi-trash-fill icon-link border-0 link-danger" />
	} = props;

	return (
		<ConfirmModal
			{ ...props }
			text={ text }
			confirm={ confirm }
			children={ children }
		/>
	);
}
