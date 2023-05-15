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
		case 'boolean':
		case 'checkbox':
			field = (
				<Form.Check
					{...props}
					onChange={ ( event ) => { onChange( event.target.checked ) } }
					label={ label ?? name }
					type="checkbox"
				/>
			);
			break;
		case 'radio':
			// @todo multiple options.
			field = (
				<Form.Check
					{...props}
					onChange={ ( event ) => { onChange( event.target.value ) } }
					label={ label ?? name }
					type="radio"
				/>
			);
			break;
		default:
			// @todo custom field types?
			field = (
				<FloatingLabel label={ label ?? name }>
					<Form.Control {...props} onChange={ ( event ) => { onChange( event.target.value ) } } />
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
