import React from 'react';
import PropTypes from 'prop-types';
import { useTranslation } from 'react-i18next';
import ChooseModal from '../ChooseModal';

export default function ConfirmModal( props ) {
	const { t } = useTranslation();

	const {
		confirm = t('Confirm'),
		callback,
		callbackProps,
		variant,
		icon,
	} = props;

	return (
		<ChooseModal
			choices={ [ { label: confirm, icon: icon, variant: variant, callback: callback, callbackProps: callbackProps } ] }
			header={ props.header }
			text={ props.text }
			trigger={ props.trigger }
			cancel={ props.cancel }
			onOpen={ props.onOpen }
			onClose={ props.onClose }
		>
			{ props.children }
		</ChooseModal>
	);
}

ConfirmModal.propTypes = {
	confirm: PropTypes.oneOfType( [ PropTypes.bool, PropTypes.string, PropTypes.node ] ),
	callback: PropTypes.func,
	callbackProps: PropTypes.any,
	variant: PropTypes.string,
	icon: PropTypes.string,
	header: PropTypes.node,
	text: PropTypes.string,
	cancel: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
	trigger: PropTypes.oneOfType( [ PropTypes.string, PropTypes.array ] ),
	onOpen: PropTypes.func,
	onClose: PropTypes.func,
	children: PropTypes.node,
};
