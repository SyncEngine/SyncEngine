import React from 'react';
import { useTranslation } from 'react-i18next';
import Columns from "../../fields/Columns";
import { getOperators } from "../../../utils/conditionals";

export default function Conditionals( props ) {
	const { t } = useTranslation();

	const {
		conditionalTypes = {},
		value,
		onChange,
	} = props;

	return (
		<Columns
			taggable={ props.taggable }
			sortable={ props.sortable ?? true }
			value={ value }
			onChange={ onChange }
			columns={ {
				key: {
					label: t( 'Field' ),
				},
				operator: {
					label: t('Operator'),
					choices: {
						'': '-- ' + t('Select') + ' --',
						...getOperators()
					},
				},
				compare: {
					label: t('Compare'),
					mutliple: true,
				}
			} }
		/>
	);
}
