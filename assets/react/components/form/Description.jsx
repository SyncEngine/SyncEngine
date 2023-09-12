import React from "react";
import { Form } from 'react-bootstrap';

export default function Description( {
	id,
	text,
	description = text,
} ) {
	return (
		<Form.Text id={ 'desc_' + id } muted>
			{ description }
		</Form.Text>
	)
}
