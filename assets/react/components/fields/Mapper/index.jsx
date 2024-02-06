import React, { useCallback, useEffect } from 'react';
import Grid from '../Grid';
import Fields from '../../form/Fields';
import useEntity from '../../../hooks/useEntity';
import { objectToMappable } from '../../../utils/data';
import { objectToTags } from '../../../utils/tags';

export default function Mapper( props ) {
	let {
		value,
		values,
		config,
		choices = 'choices',
		onChange,
	} = props;

	if ( config ) {
		// Switch to configurable mapper.
		return <Fields fields={ config } value={ value } onChange={ onChange } />
	}

	const [ sourceStorage, sourceCallbacks ] = useEntity( 'storage' );
	const [ targetStorage, targetCallbacks ] = useEntity( 'storage' );

	useEffect( () => {
		if ( 'string' === typeof choices && values.hasOwnProperty( choices ) ) {
			sourceCallbacks.get( values[ choices ].source );
			targetCallbacks.get( values[ choices ].target );
		} else {
			sourceCallbacks.get( null );
			targetCallbacks.get( null );
		}
	}, [ choices, values ] );

	const parseDataChoices = useCallback( ( data ) => {
		if ( 'object' !== typeof data ) {
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
	}, [] )

	return (
		<Grid
			taggable={ props.taggable }
			sortable={ props.sortable ?? true }
			value={ value }
			onChange={ onChange }
			columns={ {
				source: {
					label: 'From',
					choices: parseDataChoices( ( sourceStorage ) ? sourceStorage.data : choices.source ),
					customizable: props.customizable ?? true,
				},
				target: {
					label: 'To',
					choices: parseDataChoices( ( targetStorage ) ? targetStorage.data : choices.target ),
					customizable: props.customizable ?? true,
				},
			} }
		/>
	);
}
