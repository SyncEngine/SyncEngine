import React from 'react';
import Form from 'react-bootstrap/Form';

export function FieldController( props ) {

	const {
		type,
		name,
		label,
		description,
	} = props;

	return (
		<>
			{ name }
			{ type }
			{ description }
		</>
	);
}
