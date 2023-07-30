import React, { useState } from 'react';
import { Card, Collapse } from 'react-bootstrap';
import { createRefId } from '../../../utils/globals';

export default function FieldContainer( {
	id,
	label,
	help,
	description,
	collapsed,
	children,
} ) {

	const [ open, setOpen ] = useState( ! label ? true : ! collapsed );

	if ( ! id ) {
		id = createRefId();
	}

	return (
		<Card className="shadow-none border-input">
			{ label &&
				<Card.Header
					className="bg-body btn d-flex border-bottom-0"
					onClick={ () => { setOpen( ! open ) } }
					aria-controls={ id }
					aria-expanded={ open }
				>
					<span>{ label }</span>{ help }
				</Card.Header>
			}
			<Collapse in={ open } dimension="height">
				<Card.Body id={ id }>
					{ description }
					{ children }
				</Card.Body>
			</Collapse>
		</Card>
	);
}
