import React from 'react';
import { Row, Col } from "react-bootstrap";
import Help from '../../form/Help';

export default function ColumnsHead( props ) {
	const {
		columnMap,
	} = props;

	return (
		<Row className="g-1">
			{ props.sortable &&
			  <span className="bi icon-link col col-auto"><span style={ { width: '1em' } } /></span>
			}
			{
				columnMap.map( ( type, index ) => {
					return (
						<Col key={ index } >
							<div
								className="text-uppercase small text-secondary fw-semibold"
								style={ { '--bs-bg-opacity': '.05' } }
							>
								{ type.label ?? type.name ?? '' }
								{ type.help && <Help text={ type.help } /> }
							</div>
						</Col>
					)
				} )
			}
			{ props.removable &&
			  <span className="bi icon-link col col-auto"><span style={ { width: '1em' } } /></span>
			}
		</Row>
	);
}
