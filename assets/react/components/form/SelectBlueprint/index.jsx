import React from 'react';
import { useTranslation } from 'react-i18next';
import Select from '../../fields/Select/Advanced';

export default function SelectBlueprint( props ) {
	const { t } = useTranslation();

	return (
		<Select
			choices={ props.options }
			filters={ { key: 'type' } }
			label={ t('Select Blueprint') }
			placeholder={ '-- ' + t('Select Blueprint') + ' --' }
			group={ { key: 'module', fallback: t('Core') } }
			value=""
			{ ...props }
		/>
	)
}
