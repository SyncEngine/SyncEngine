import React, { useCallback, useEffect } from 'react';
import Columns from '../Columns';
import Fields from '../../form/Fields';
import useEntity from '../../../hooks/useEntity';

export default function Mapper( props ) {
	let {
		sourceKeys = {},
		targetKeys = {},
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

	const [ sourceDataset, sourceCallbacks ] = useEntity( 'dataset' );
	const [ targetDataset, targetCallbacks ] = useEntity( 'dataset' );

	useEffect( () => {
		if ( 'string' === typeof choices && values.hasOwnProperty( choices ) ) {
			sourceCallbacks.get( values[ choices ].source );
			targetCallbacks.get( values[ choices ].target );
		} else {
			sourceCallbacks.get( null );
			targetCallbacks.get( null );
		}
	}, [ choices, values ] );

	const getDataChoices = useCallback( ( data ) => {
		for ( const key in data ) {
			if ( ! data.hasOwnProperty( key ) ) {
				continue;
			}
			if ( 'object' !== typeof data[ key ] ) {
				continue;
			}
			data[ key ] = data[ key ].label ?? data[ key ].name ?? key;
		}
		return data;
	}, [] );

	return (
		<Columns
			taggable={ props.taggable }
			sortable={ props.sortable ?? true }
			value={ value }
			onChange={ onChange }
			columns={ {
				source: {
					label: 'From',
					choices: ( sourceDataset ) ? getDataChoices( sourceDataset.data ) : sourceKeys,
					customizable: props.customizable ?? true,
				},
				target: {
					label: 'To',
					choices: ( targetDataset ) ? getDataChoices( targetDataset.data ) : targetKeys,
					customizable: props.customizable ?? true,
				},
			} }
		/>
	);
}
