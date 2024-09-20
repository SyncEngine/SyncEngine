import React from 'react';
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
