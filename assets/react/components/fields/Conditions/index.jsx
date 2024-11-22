import React from 'react';
import { any, array, bool, func, object, oneOfType } from 'prop-types';
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
			id={ props.id }
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

Conditions.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	sortable: bool,
	taggable: bool,
	conditionTypes: oneOfType( [ object, array ] ),
}
