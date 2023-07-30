import React from "react";
import { Card } from 'react-bootstrap';

export default function FieldContainer( {
	label,
	help,
	description,
	children,
} ) {

	return (
		<Card className="shadow-none border-input">
			{ label &&
				<Card.Header className="bg-body">
					<span>{ label }</span>{ help }
				</Card.Header>
			}
			<Card.Body>
				{ description }
				{ children }
			</Card.Body>
		</Card>
	);
}
