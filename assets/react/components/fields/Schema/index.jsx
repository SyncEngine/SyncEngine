import React, { useCallback, useContext } from 'react';
import Grid from '../Grid';
import { objectToMappable } from '../../../utils/data';
import { objectToTags } from '../../../utils/tags';
import { useTranslation } from 'react-i18next';
import { DataContext } from '../../../context/DataContext';

export default function Schema( props ) {
	const { t } = useTranslation();
	let {
		value,
		source,
		choices,
		onChange,
	} = props;

	const dataContext = useContext( DataContext );

	const parseDataChoices = useCallback( ( source ) => {

		if ( 'data' !== source ) {
			return [];
		}

		let data = dataContext;

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
	}, [] )

	return (
		<Grid
			taggable={ props.taggable }
			sortable={ props.sortable ?? true }
			value={ value }
			onChange={ onChange }
			columns={ {
				key: {
					label: t('Column key/name'),
					choices: ( source ) ? parseDataChoices( source ) : choices ?? [],
					customizable: props.customizable ?? true,
				},
				column: {
					label: t('Column type'),
					type: 'column',
				},
			} }
		/>
	);
}
