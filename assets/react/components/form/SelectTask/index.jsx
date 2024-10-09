import React from 'react';
import { useTranslation } from 'react-i18next';
import Select from '../../fields/Select/Advanced';

export default function SelectTask( props ) {
	const { t } = useTranslation();

	return (
		<Select
			choices={ props.options }
			filters={ { key: 'type' } }
			label={ t('Select Task') }
			placeholder={ '-- ' + t('Select Task') + ' --' }
			group="module"
			value=""
			{ ...props }
		/>
	)
}
