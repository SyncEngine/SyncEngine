import React from "react";
import { InputGroup, OverlayTrigger, Tooltip } from "react-bootstrap";

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

	return (
		<OverlayTrigger overlay={ <Tooltip id={ id }>{ text }</Tooltip> }>
			{ button }
		</OverlayTrigger>
	)
}
