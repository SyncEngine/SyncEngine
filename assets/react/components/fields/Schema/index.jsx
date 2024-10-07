import React, { useContext } from 'react';
import { any, bool, func, object, string } from 'prop-types';
import { useTranslation } from 'react-i18next';

import { DataContext } from '../../../context/DataContext';
import Grid from '../Grid';
import Icon from '../../partials/Icon';
import { objectToMappable } from '../../../utils/data';
import { objectToTags } from '../../../utils/tags';
import { isFieldEditable } from '../../../utils/conditions';

function parseDataChoices( source, data ) {
	if ( 'data' !== source ) {
		return [];
	}

	if ( ! Array.isArray( data ) ) {
		data = objectToMappable( objectToTags( data ), 'value', 'label', true );
	}

	return data.map( row => {
		if ( 'object' !== typeof row ) {
			return { value: row, label: row };
		}

		const value = String( row.value || row.key || row.name || row.label );
		const label = String( row.label || row.name || row.value || row.key );

		return {
			value: value,
			label: ( label !== value ) ? label + '  <' + value + '>' : value,
		}
	} );
}

export default function Schema( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );
	let {
		value,
		source,
		choices,
		onChange,
	} = props;

	const dataContext = useContext( DataContext );

	return (
		<Grid
			editable={ editable }
			taggable={ props.taggable }
			sortable={ props.sortable ?? editable }
			disabled={ props.disabled }
			value={ value }
			onChange={ onChange }
			columns={ {
				key: {
					label: t('Column key/name'),
					choices: ( source ) ? parseDataChoices( source, dataContext ) : choices ?? [],
					customizable: props.customizable ?? true,
				},
				column: {
					label: t('Column type'),
					type: 'column',
				},
				required: {
					"aria-label": t( 'Required' ),
					label: ( <Icon icon="required" /> ),
					help: t( 'Required' ),
					header: '',
					type: 'toggle',
					button: 'outline-danger',
					toggle: true,
					style: { width: '3em', flex: 'initial', textAlign: 'center', justifyContent: 'center' },
				},
				readonly: {
					"aria-label": t( 'Read only' ),
					label: ( <Icon icon="readonly" /> ),
					help: t( 'Read only' ),
					header: '',
					type: 'toggle',
					button: 'outline-info',
					toggle: true,
					style: { width: '3em', flex: 'initial', textAlign: 'center', justifyContent: 'center' },
				},
			} }
		/>
	);
}

Schema.propTypes = {
	onChange: func,
	value: any,
	default: any,
	editable: bool,
	disabled: bool,
	readonly: bool,
	choices: object,
	source: string,
}
