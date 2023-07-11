import React, { useState } from 'react';
import { Stack } from 'react-bootstrap';

import ColumnsRow from "./Row";
import { objectToMappable } from "../../../utils/data";
import { isEmpty } from "../../../utils/conditionals";
import ColumnsHead from "./Head";

export default function Columns( props ) {
	const [ value, setValue ] = useState( ( Array.isArray( props.value ) && props.value.length ) ? [ ...props.value ] : [] );

	const {
		columns: columns = {
			key: 'Key',
			value: 'Value',
		},
		nest = false,
		onChange,
	} = props;

	const columnMap = objectToMappable( columns, 'name', 'label' );

	const updateValue = ( newValue ) => {
		setValue( newValue );
		onChange( newValue );
	}

	const updateIndex = ( index, newValue ) => {
		let filteredValue = [];

		// Set new value.
		value[ index ] = { ...newValue };

		// Remove empty values.
		for ( let i = 0; i < value.length; i++ ) {
			if ( ! Object.values( value[ i ] ).every( x => isEmpty( x ) ) ) {
				filteredValue.push( value[ i ] );
			}
		}

		updateValue( filteredValue );
	}

	if ( ! value.length || ! isEmpty( value[ value.length - 1 ] ) ) {
		value.push( {} );
	}

	return (
		<Stack gap="1">
			<ColumnsHead columnMap={ columnMap } />
			{
				value.map( ( row, index ) => {
					return (
						<ColumnsRow key={ index } data={ row } columnMap={ columnMap } nest={ nest } onChange={ ( value ) => { updateIndex( index, value ) } } />
					)
				} )
			}
		</Stack>
	);
}
