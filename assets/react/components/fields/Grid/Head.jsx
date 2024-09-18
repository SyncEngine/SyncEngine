import React from 'react';
import { Col, Row } from 'react-bootstrap';
import Help from '../../form/Help';

export default function GridHead( props ) {
	const {
		columnMap,
	} = props;

	return (
		<thead className="position-sticky top-0 z-1 bg-body">
			<tr>
				{ props.sortable && <th className="table-cell-shrink position-sticky start-0 bg-body z-2"></th> }
				<th>
					<Row className="g-1 flex-nowrap">
						{
							columnMap.map( ( type, index ) => {
								return (
									<Col key={ index } style={ type.style ?? { minWidth: 200 } }>
										<div
											className="text-uppercase small text-secondary fw-semibold"
											style={ { '--bs-bg-opacity': '.05' } }
										>
											{ type.header ?? type.label ?? type.name ?? '' }
											{ type.help && <Help text={ type.help } /> }
										</div>
									</Col>
								)
							} )
						}
					</Row>
				</th>
				{ props.removable && <th className="table-cell-shrink position-sticky end-0 bg-body z-2"></th> }
			</tr>
		</thead>
	);
}
