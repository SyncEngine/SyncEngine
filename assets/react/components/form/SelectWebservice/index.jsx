import React from 'react';
import { useTranslator } from '../../../hooks/useTranslator.js';
import Select from '../../fields/Select/Advanced';

export default function SelectWebservice( props ) {
	const { t } = useTranslator();

	return (
		<Select
			choices={ props.options }
			filters={ { key: 'type' } }
			label={ t('Select Webservice') }
			placeholder={ '-- ' + t('Select Webservice') + ' --' }
			group={ { key: 'module', fallback: t('Core') } }
			value=""
			{ ...props }
		/>
	)
}
