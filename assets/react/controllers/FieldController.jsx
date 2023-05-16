import React from 'react';
import Form from 'react-bootstrap/Form';
import FloatingLabel from 'react-bootstrap/FloatingLabel';
import Mapper from "../components/Mapper";
import Params from "../components/Params";
import { objectToMappable } from "../utils/format";

export default function FieldController( props ) {

	const {
		value: value = {},
		type,
		name,
		label: label = props.name,
		description,
		onChange,
	} = props;

	let field;

	switch ( type ) {
		case 'mapper':
			field = (
				<>
					{ label }
					<Mapper {...props} />
					{
						description &&
						<Form.Text>
							{ description }
						</Form.Text>
					}
				</>
			);
			break;
		case 'params':
			field = (
				<>
					{ label }
					<Params {...props} />
					{
						description &&
						<Form.Text>
							{ description }
						</Form.Text>
					}
				</>
			);
			break;
		case 'boolean':
		case 'checkbox':
			field = (
				<Form.Check
					{...props}
					onChange={ ( event ) => { onChange( event.target.checked ) } }
					label={ label }
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
					label={ label }
					type="radio"
				/>
			);
			break;
		case 'select':
		case 'entity': // @todo Custom component.
			field = (
				<Form.Select
					{...props}
					onChange={ ( event ) => { onChange( event.target.value ) } }
					label={ label }
					type="radio"
				>
					{
						objectToMappable( props.choices ?? props.options, 'value', 'label' ).map( ( option, index ) => {
							return <option key={ index } value={ option.value }>{ option.label ?? option.value }</option>
						} )
					}
				</Form.Select>
			);
		default:
			// @todo custom field types?
			field = (
				<FloatingLabel label={ label }>
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
