import React from 'react';
import { useTranslation } from 'react-i18next';
import ConfirmModal from '../ConfirmModal';

export default function DeleteModal( props ) {
	const { t } = useTranslation();

	const {
		text = t('Are you sure?'),
		confirm = t('Delete'),
	} = props;

	const getTrigger = () => {
		if ( 'function' === typeof props.children ) {
			return props.children;
		} else if ( React.isValidElement( props.children ) ) {
			return props.children;
		}
		return <span className="bi bi-trash-fill icon-btn border-0 text-danger-hover" />;
	}

	return (
		<ConfirmModal
			{ ...props }
			text={ text }
			confirm={ confirm }
			variant="outline-danger"
			icon="bi-trash-fill"
		>
			{ getTrigger() }
		</ConfirmModal>
	);
}
