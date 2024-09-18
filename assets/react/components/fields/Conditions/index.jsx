import React from 'react';
import { useTranslation } from 'react-i18next';
import Grid from '../../fields/Grid';
import { getOperators, isFieldEditable } from '../../../utils/conditions';

export default function Conditions( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );

	const {
		conditionTypes = {},
		value,
		onChange,
	} = props;

	return (
		<Grid
			editable={ editable }
			taggable={ props.taggable }
			sortable={ props.sortable ?? editable }
			disabled={ props.disabled }
			value={ value }
			onChange={ onChange }
			columns={ {
				source: {
					label: t( 'Source' ),
					placeholder: '{{ data }}',
				},
				key: {
					label: t( 'Field' ),
				},
				operator: {
					label: t( 'Operator' ),
					choices: {
						...getOperators(),
					},
				},
				compare: {
					label: t( 'Compare' ),
					mutliple: true,
				},
			} }
		/>
	);
}
