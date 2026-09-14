import React from 'react';
import { useTranslator } from '../../../hooks/useTranslator.js';
import Select from '../../fields/Select/Advanced';

export default function SelectTask( props ) {
	const { t } = useTranslator();

	return (
		<Select
			choices={ props.options }
			filters={ { key: 'type' } }
			label={ t('Select Task') }
			placeholder={ '-- ' + t('Select Task') + ' --' }
			group={ { key: 'module', fallback: t('Core') } }
			value=""
			{ ...props }
		/>
	)
}
