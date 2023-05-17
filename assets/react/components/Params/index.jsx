import React, { useState } from 'react';
import Stack from 'react-bootstrap/Stack';
import Form from 'react-bootstrap/Form';
import Col from 'react-bootstrap/Col';
import Row from 'react-bootstrap/Row';
import { objectToMappable } from "../../utils/format";

export default function Params( props ) {
	const [ params, setParams ] = useState( [{}] );

	let {
		columns: columns = {
			key: 'Key',
			value: 'Value',
		},
		onChange,
	} = props;

	columns = objectToMappable( columns, 'name', 'label' );

	const updateIndex = ( index, value ) => {
		let newParams = [...params];

		newParams[ index ] = value;

		// Remove empty rows.
		newParams = newParams.filter( ( value ) => {
			return ! Object.values( value ).every( x => x === null || x === '' );
		} );

		// Append single empty row at the end.
		newParams.push( {} );

		setParams( newParams );
		onChange( params );
	}

	const update = ( index, type, value ) => {
		let param = params[ index ];
		param[ type ] = value;
		updateIndex( index, param );
	}

	return (
		<Stack gap="1">
			<Row>
				{
					columns.map( ( type, index ) => {
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
						<Row key={ index }>
							{
								columns.map( ( type, typeIndex ) => {
									const {
										name: typeName = '',
										label: typeLabel = '',
									} = type;

									const predefined = ( type.hasOwnProperty( 'predefined' ) && Object.keys( type.predefined ).length ) ? type.predefined : null;
									const value = ( data.hasOwnProperty( typeName ) ) ? data[ typeName ] : '';

									return (
										<Col key={ typeIndex }>
											{ predefined &&
											  <Form.Select aria-label="" value={ value } onChange={ ( event ) => { update( index, typeName, event.target.value ) } }>
												  {
													  objectToMappable( predefined, 'value', 'label' ).map( ( paramTypeKey, keyIndex ) => {
														  return <option key={ keyIndex } value={ paramTypeKey.value }>{ paramTypeKey.label ?? paramTypeKey.value }</option>
													  } )
												  }
											  </Form.Select>
											}
											{ ! predefined &&
											  <Form.Control type="text" value={ value } onChange={ ( event ) => { update( index, typeName, event.target.value ) } } />
											}
										</Col>
									)
								} )
							}
						</Row>
					)
				} )
			}
		</Stack>
	);
}
