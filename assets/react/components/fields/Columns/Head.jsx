import React from 'react';
import Row from 'react-bootstrap/Row';
import { Col } from "react-bootstrap";

export default function ColumnsHead( props ) {
	const {
		columnMap,
	} = props;

	return (
		<Row className="g-1">
			{
				columnMap.map( ( type, index ) => {
					return (
						<Col key={ index } >
							<div
								className="text-uppercase small text-secondary fw-semibold"
								style={ { '--bs-bg-opacity': '.05' } }
							>
								{ type.label ?? type.name ?? '' }
							</div>
						</Col>
					)
				} )
			}
		</Row>
	);
}
