import React from 'react';
import FloatingLabel from 'react-bootstrap/FloatingLabel';
import Form from 'react-bootstrap/Form';
import Mapper from "../components/Mapper";

export default function FieldController( props ) {

	const {
		value: value = {},
		type,
		name,
		label,
		description,
		onChange,
	} = props;

	let field;

	switch ( type ) {
		case 'mapper':
			field = ( <Mapper {...props} /> );
			break;
		default:
			// @todo custom field types?
			field = (
				<FloatingLabel label={ label ?? name }>
					<Form.Control {...props} />
					{
						description &&
						<Form.Text>
							{ description }
						</Form.Text>
					}
				</FloatingLabel>
			);
			break;
	}

	return (
		<>
			{ field }
		</>
	);
}
