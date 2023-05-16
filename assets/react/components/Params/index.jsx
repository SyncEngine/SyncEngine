import React, { useState } from 'react';
import Form from 'react-bootstrap/Form';
import Col from 'react-bootstrap/Col';
import Row from 'react-bootstrap/Row';
import { objectToMappable } from "../../utils/format";

export default function Params( props ) {
	const [ params, setParams ] = useState( [{}] );

	let {
		paramTypes: paramTypes = [],
		onChange,
	} = props;

	paramTypes = objectToMappable( paramTypes, 'name', 'label' );

	const updateIndex = ( index, value ) => {
		let newParams = [...params];

		newParams[ index ] = value;

		// Remove empty rows.
		newParams = newParams.filter( ( value ) => {
			return Object.values( value ).every( x => x === null || x === '' );
		} );

		// Append single empty row at the end.
		newParams.push( {} );

		setParams( newParams );
		onChange( params );
	}

	const update = ( index, type, value ) => {
		let param = params[ index ];
		param.source = value;
		updateIndex( index, param );
	}

	return (
		<>
			<Row>
				{
					paramTypes.map( ( type, index ) => {
						return (
							<Col key={ index }>
								type.label
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
								paramTypes.map( ( type, typeIndex ) => {
									let {
										type: typeName = '',
										label: typeLabel = '',
										predefined: predefined = [],
									} = type;

									if ( ! predefined.length ) {
										predefined = null;
									}

									const value = data.hasOwnProperty( type ) && data[ type ];

									return (
										<Col index={ typeIndex }>
											{ predefined &&
											  <Form.Select aria-label="" value={ value } onChange={ ( event ) => { update( index, typeName, event.target.value ) } }>
												  {
													  objectToMappable( predefined, 'value', 'label' ).map( ( paramTypeKey, keyIndex ) => {
														  return <option value={ paramTypeKey.value } key={ keyIndex }>{ paramTypeKey.label ?? paramTypeKey.value }</option>
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
		</>
	);
}
