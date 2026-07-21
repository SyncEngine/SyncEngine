import React from 'react';
import PropTypes from 'prop-types';
import { useTranslation } from 'react-i18next';
import ConfirmModal from '../ConfirmModal';
import Icon from '../../partials/Icon';

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
		return <Icon icon="delete" className="icon-btn border-0 text-danger-hover" />;
	}

	return (
		<ConfirmModal
			{ ...props }
			callbackProps={ props.entity ?? props.item ?? props.id }
			text={ text }
			confirm={ confirm }
			variant="outline-danger"
			icon="delete"
		>
			{ getTrigger() }
		</ConfirmModal>
	);
}

DeleteModal.propTypes = {
	text: PropTypes.string,
	confirm: PropTypes.oneOfType( [ PropTypes.bool, PropTypes.string, PropTypes.node ] ),
	children: PropTypes.node,
	entity: PropTypes.object,
	item: PropTypes.object,
	id: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] ),
};
