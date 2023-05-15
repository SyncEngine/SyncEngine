import { useState } from "react";
import Form from 'react-bootstrap/Form';
import Col from 'react-bootstrap/Col';
import Row from 'react-bootstrap/Row';

export function Mapper( props ) {
	const [ mapper, setMapper ] = useState([]);

	let {
		sourceKeys,
		targetKeys,
	} = props;

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
								<Form.Select aria-label="" value={ source }>
									{
										sourceKeys.map( ( sourceKey, index ) => {
											return <option value={ sourceKey } key={ index }>{ sourceKey }</option>
										} )
									}
								</Form.Select>
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
