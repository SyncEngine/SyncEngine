import React from 'react';
import { Tooltip } from 'react-bootstrap';

export default function Ref( props ) {
	const {
		entity,
		item = entity,
	} = props;

	return (
		<>
			<OverlayTrigger overlay={ <Tooltip id={ item.ref }>{ item.ref }</Tooltip> }>
				{ item.id }
			</OverlayTrigger>
		</>
	)
}
