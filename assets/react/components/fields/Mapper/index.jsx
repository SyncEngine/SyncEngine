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
		choices,
		onChange,
	} = props;

	if ( config ) {
		// Switch to configurable mapper.
		return <Fields fields={ config } value={ value } onChange={ onChange } />
	}

	const [ sourceDataset, sourceCallbacks ] = useEntity( 'dataset' );
	const [ targetDataset, targetCallbacks ] = useEntity( 'dataset' );

	useEffect( () => {
		if ( choices && values.hasOwnProperty( choices ) ) {
			sourceCallbacks.get( values[ choices ].source );
			targetCallbacks.get( values[ choices ].target );
		} else {
			sourceCallbacks.get( null );
			targetCallbacks.get( null );
		}
	}, [ choices, values ] );

	return (
		<Columns
			taggable={ props.taggable }
			sortable={ props.sortable ?? true }
			value={ value }
			onChange={ onChange }
			columns={ {
				source: {
					label: 'From',
					choices: ( sourceDataset ) ? sourceDataset.data : sourceKeys,
				},
				target: {
					label: 'To',
					choices: ( targetDataset ) ? targetDataset.data : targetKeys,
				},
			} }
		/>
	);
}
