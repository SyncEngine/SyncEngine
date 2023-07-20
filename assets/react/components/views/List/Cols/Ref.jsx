import React from 'react';
import { Tooltip, OverlayTrigger } from 'react-bootstrap';

export default function Ref( props ) {
	const {
		item,
	} = props;

	return (
		<OverlayTrigger overlay={ <Tooltip id={ item.ref }>{ item.ref }</Tooltip> }>
			<span>{ item.id }</span>
		</OverlayTrigger>
	)
}
