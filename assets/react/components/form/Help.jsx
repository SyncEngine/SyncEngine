import React from 'react';
import { InputGroup, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { objectToMappable } from '../../utils/data';

export default function Help( {
	id,
	text,
	inputGroup,
} ) {

	if ( ! text ) {
		return;
	}

	let button = <span className="bi bi-info-circle mx-2" />;
	if ( inputGroup ) {
		button = <InputGroup.Text><span className="bi bi-info-circle" /></InputGroup.Text>;
	}

	let help = text;

	if ( 'object' === typeof text ) {
		help = (
			<ul className="text-start m-1 ms-n2">
				{
					objectToMappable( text, 'type', 'text' ).map( ( item, index ) => {
						return <li key={ index }>{ item.text }</li>
					} )
				}
			</ul>
		)
	}

	return (
		<OverlayTrigger overlay={ <Tooltip id={ id + '_help' }>{ help }</Tooltip> }>
			{ button }
		</OverlayTrigger>
	)
}
