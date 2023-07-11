import React, { useState } from 'react';
import { Stack } from 'react-bootstrap';

import ParamsRow from "./Row";
import { objectToMappable } from "../../../utils/data";
import { isEmpty } from "../../../utils/conditionals";
import ParamsHead from "./Head";

export default function ParamsColumns( props ) {
	const [ params, setParams ] = useState( ( Array.isArray( props.value ) && props.value.length ) ? [ ...props.value ] : [] );

	const {
		columns: columns = {
			key: 'Key',
			value: 'Value',
		},
		nest = false,
		onChange,
	} = props;

	const columnMap = objectToMappable( columns, 'name', 'label' );

	const updateParams = ( newParams ) => {
		setParams( newParams );
		onChange( [ ...newParams ] );
	}

	const updateIndex = ( index, value ) => {
		let newParams = [ ...params ],
			filteredParams = [];

		// Set new value.
		newParams[ index ] = { ...value };

		// Remove empty values.
		for ( let i = 0; i < newParams.length; i++ ) {
			if ( ! Object.values( newParams[ i ] ).every( x => isEmpty( x ) ) ) {
				filteredParams.push( newParams[ i ] );
			}
		}

		updateParams( filteredParams );
	}

	if ( ! params.length || ! isEmpty( params[ params.length - 1 ] ) ) {
		params.push( {} );
	}

	return (
		<Stack gap="1">
			<ParamsHead columnMap={ columnMap } />
			{
				params.map( ( data, index ) => {
					return (
						<ParamsRow key={ index } data={ data } columnMap={ columnMap } nest={ nest } onChange={ ( value ) => { updateIndex( index, value ) } } />
					)
				} )
			}
		</Stack>
	);
}
