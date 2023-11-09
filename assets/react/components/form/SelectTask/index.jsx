import React from 'react';
import { useTranslation } from 'react-i18next';
import Select from "../Select";

export default function SelectTask( props ) {
	const { t } = useTranslation();

	return (
		<Select
			choices={ props.options }
			filterKey="type"
			filterLabel={ t('All') }
			label={ t('Select Task') }
			placeholder={ '-- ' + t('Select Task') + ' --' }
			group="module"
			value=""
			{...props}
		/>
	)
}
