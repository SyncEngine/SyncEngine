import React from "react";
import { Card } from 'react-bootstrap';

export default function FieldContainer( props ) {

	const {
		label,
		help,
		description,
	} = props;

	return (
		<Card className="shadow-none border-input">
			<Card.Body>
				{ label &&
					<div className="mt-n1 mb-2"><span>{ label }</span>{ help }</div>
				}
				{ description }
				{ props.children }
			</Card.Body>
		</Card>
	);
}
