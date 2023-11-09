import React from 'react';
import { useTranslation } from 'react-i18next';
import Select from "../Select";

export default function SelectWebservice( props ) {
	const { t } = useTranslation();

	return (
		<Select
			choices={ props.options }
			filterKey="type"
			filterLabel={ t('All') }
			label={ t('Select Webservice') }
			placeholder={ '-- ' + t('Select Webservice') + ' --' }
			group="module"
			value=""
			{...props}
		/>
	)
}
