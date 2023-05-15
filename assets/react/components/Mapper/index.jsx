import React, { useState } from 'react';
import Form from 'react-bootstrap/Form';
import Col from 'react-bootstrap/Col';
import Row from 'react-bootstrap/Row';

export default function Mapper( props ) {
	const [ mapper, setMapper ] = useState( [{}] );

	let {
		sourceKeys: sourceKeys = [],
		targetKeys: targetKeys = [],
		onChange,
	} = props;

	if ( ! sourceKeys.length ) {
		sourceKeys = null;
	}
	if ( ! targetKeys.length ) {
		targetKeys = null;
	}

	const updateMap = ( value, index ) => {
		let newMapper = [...mapper];

		newMapper[ index ] = value;

		// Remove empty rows.
		newMapper = newMapper.filter( ( value ) => {
			return (
				( value.hasOwnProperty( 'source' ) && value.source.length ) ||
				( value.hasOwnProperty( 'target' ) && value.target.length )
			);
		} );

		// Append single empty row at the end.
		newMapper.push( {} );

		console.log( newMapper );

		setMapper( newMapper );
		onChange( mapper );
	}

	const updateSource = ( value, index ) => {
		let map = mapper[ index ];
		map.source = value;
		updateMap( map, index );
	}

	const updateTarget = ( value, index ) => {
		let map = mapper[ index ];
		map.target = value;
		updateMap( map, index );
	}

	console.log( mapper );

	return (
		<>
			<Row>
				<Col>
					Source
				</Col>
				<Col>
					Target
				</Col>
			</Row>
			{
				mapper.map( ( data, index ) => {
					const {
						source: source = '',
						target: target = '',
					} = data;

					return (
						<Row key={ index }>
							<Col>
								{ sourceKeys &&
									<Form.Select aria-label="" value={ source } onChange={ ( event ) => { updateSource( event.target.value, index ) } }>
										{
											sourceKeys.map( ( sourceKey, index ) => {
												return <option value={ sourceKey } key={ index }>{ sourceKey }</option>
											} )
										}
									</Form.Select>
								}
								{ ! sourceKeys &&
									<Form.Control type="text" value={ source } onChange={ ( event ) => { updateSource( event.target.value, index ) } } />
								}
							</Col>

							<Col>
								{ targetKeys &&
								  <Form.Select aria-label="" value={ target } onChange={ ( event ) => { updateTarget( event.target.value, index ) } }>
									  {
										  targetKeys.map( ( targetKey, index ) => {
											  return <option value={ targetKey } key={ index }>{ targetKey }</option>
										  } )
									  }
								  </Form.Select>
								}
								{ ! targetKeys &&
									<Form.Control type="text" value={ target } onChange={ ( event ) => { updateTarget( event.target.value, index ) } } />
								}
							</Col>
						</Row>
					)
				} )
			}
		</>
	);
}
