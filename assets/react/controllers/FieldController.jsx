import React from 'react';
import Form from 'react-bootstrap/Form';
import { Mapper } from "../components/Mapper";

export function FieldController( props ) {

	const {
		type,
		name,
		label,
		description,
		value,
	} = props;

	let field;

	switch ( type ) {
		case 'mapper':
			field = ( <Mapper {...props} /> );
			break;
		default:
			// @todo custom field types?
			field = ( <Form.Control {...props} /> );
			break;
	}

	return (
		<>
			{ name }
			{ type }
			{ description }
			{ field }
		</>
	);
}
