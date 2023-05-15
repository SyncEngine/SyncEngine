import React, { useState } from 'react';
import Form from 'react-bootstrap/Form';
import Col from 'react-bootstrap/Col';
import Row from 'react-bootstrap/Row';

export default function Mapper( props ) {
	const [ mapper, setMapper ] = useState( [{}] );

	let {
		sourceKeys: sourceKeys = [],
		targetKeys: targetKeys = [],
	} = props;

	if ( ! sourceKeys.length ) {
		sourceKeys = null;
	}
	if ( ! targetKeys.length ) {
		targetKeys = null;
	}

	const updateMap = ( values ) => {
		// Parser..
		setMapper( values );
	}

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
						source,
						target,
					} = data;

					return (
						<Row key={ index }>
							<Col>
								{ sourceKeys &&
									<Form.Select aria-label="" value={ source }>
										{
											sourceKeys.map( ( sourceKey, index ) => {
												return <option value={ sourceKey } key={ index }>{ sourceKey }</option>
											} )
										}
									</Form.Select>
								}
								{ ! sourceKeys &&
									<Form.Control type="text" value={ source } />
								}
							</Col>

							<Col>
								{ targetKeys &&
								  <Form.Select aria-label="" value={ target }>
									  {
										  targetKeys.map( ( targetKey, index ) => {
											  return <option value={ targetKey } key={ index }>{ targetKey }</option>
										  } )
									  }
								  </Form.Select>
								}
								{ ! targetKeys &&
									<Form.Control type="text" value={ target } />
								}
							</Col>
						</Row>
					)
				} )
			}
		</>
	);
}
