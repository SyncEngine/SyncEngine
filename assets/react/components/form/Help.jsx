import React from "react";
import { InputGroup, OverlayTrigger, Tooltip } from "react-bootstrap";
import { BiInfoCircle } from "react-icons/bi";

export default function Help( props ) {
	const {
		id,
		text,
		inputGroup,
	} = props;

	if ( ! text ) {
		return;
	}

	let button = <span><BiInfoCircle className="mx-2" /></span>;
	if ( inputGroup ) {
		button = <InputGroup.Text><BiInfoCircle /></InputGroup.Text>;
	}

	return (
		<OverlayTrigger overlay={ <Tooltip id={ id }>{ text }</Tooltip> }>
			{ button }
		</OverlayTrigger>
	)
}
