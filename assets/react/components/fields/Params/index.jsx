import React, { useState } from 'react';
import { Stack, Col, Row } from 'react-bootstrap';

import ParamsRow from "./Row";
import { objectToMappable } from "../../../utils/format";
import { isEmpty } from "../../../utils/conditionals";

export default function Params( props ) {
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
			<Row>
				{
					columnMap.map( ( type, index ) => {
						return (
							<Col key={ index } >
								<small>{ type.label }</small>
							</Col>
						)
					} )
				}
			</Row>
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
