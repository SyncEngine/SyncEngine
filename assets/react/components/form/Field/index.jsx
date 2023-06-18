import React from 'react';
import Card from 'react-bootstrap/Card';
import Form from 'react-bootstrap/Form';
import FloatingLabel from 'react-bootstrap/FloatingLabel';
import Mapper from "../../fields/Mapper";
import Params from "../../fields/Params";
import Conditions from "../../fields/Conditions";
import EntityField from "../../fields/Entity";
import { objectToMappable } from "../../../utils/format";
import { createRefId } from "../../../utils/globals";

export default function Field( props ) {

	const {
		id: id = createRefId(),
		type,
		label: label = props.name,
		description,
		onChange,
	} = props;

	let field;

	switch ( type ) {
		case 'conditions':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1"><small className="text-secondary">{ label }</small></div>
						{
							description &&
							<Form.Text>
								{ description }
							</Form.Text>
						}
						<Conditions {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'mapper':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1"><small className="text-secondary">{ label }</small></div>
						{
							description &&
							<Form.Text>
								{ description }
							</Form.Text>
						}
						<Mapper {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'params':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1"><small className="text-secondary">{ label }</small></div>
						{
							description &&
							<Form.Text>
								{ description }
							</Form.Text>
						}
						<Params {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'boolean':
		case 'checkbox':
			field = (
				<>
					<Form.Check
						{...props}
						onChange={ ( event ) => { onChange( event.target.checked ) } }
						label={ label }
						checked={ props.value }
						type="checkbox"
					/>
					{
						description &&
						<Form.Text>
							{ description }
						</Form.Text>
					}
				</>
			);
			break;
		case 'radio':
			// @todo multiple options.
			field = (
				<>
					<Form.Check
						{...props}
						onChange={ ( event ) => { onChange( event.target.value ) } }
						label={ label }
						checked={ props.value }
						type="radio"
					/>
					{
						description &&
						<Form.Text>
							{ description }
						</Form.Text>
					}
				</>
			);
			break;
		case 'select':
			let choices = props.choices ?? props.options ?? {};
			field = (
				<FloatingLabel label={ label }>
					<Form.Select
						{...props}
						onChange={ ( event ) => { onChange( event.target.value ) } }
						label={ label }
						type="radio"
					>
						<option>{ props.selectLabel ?? '-- Select --' }</option>
						{
							objectToMappable( choices, 'value', 'label' ).map( ( option, index ) => {
								return <option key={ index } value={ option.value }>{ option.label ?? option.value }</option>
							} )
						}
					</Form.Select>
					{
						description &&
						<Form.Text>
							{ description }
						</Form.Text>
					}
				</FloatingLabel>
			);
			break;
		case 'entity':
			field = (
				<EntityField {...props} />
			);
			break;
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

	return field;
}
