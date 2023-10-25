import React, { useContext } from 'react';
import ConfirmModal from '../ConfirmModal';

export default function DeleteModal( props ) {
	const {
		text = 'Are you sure?',
		confirm = 'Delete',
	} = props;

	const getTrigger = () => {
		if ( 'function' === typeof props.children ) {
			return props.children;
		} else if ( React.isValidElement( props.children ) ) {
			return props.children;
		}
		return <span className="bi bi-trash-fill icon-link border-0 link-danger" />;
	}

	return (
		<ConfirmModal
			{ ...props }
			text={ text }
			confirm={ confirm }
		>
			{ getTrigger() }
		</ConfirmModal>
	);
}
