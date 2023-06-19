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
import { InputGroup, OverlayTrigger, Tooltip } from "react-bootstrap";
import { BiInfoCircle } from "react-icons/bi";

export default function Field( props ) {

	const {
		id: id = createRefId(),
		type,
		label: label = props.name,
		description,
		onChange,
	} = props;

	let field;

	const help = props.help && (
         <OverlayTrigger overlay={ <Tooltip id={ 'help' + id }>{ props.help }</Tooltip> }>
             <InputGroup.Text><BiInfoCircle></BiInfoCircle></InputGroup.Text>
         </OverlayTrigger>
	)

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
				<>
					<InputGroup>
						{ help }
						<FloatingLabel label={ label }>
							<Form.Select
								{...props}
								placeholder={ props.placeholder ?? props.label }
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
						</FloatingLabel>
					</InputGroup>
					{
						description &&
						<Form.Text>
							{ description }
						</Form.Text>
					}
				</>
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
				<>
					<InputGroup>
						{ help }
						<FloatingLabel label={ label }>
							<Form.Control
								{...props}
								placeholder={ props.placeholder ?? ' ' }
								onChange={ ( event ) => { onChange( event.target.value ) } }
							/>
						</FloatingLabel>
					</InputGroup>
					{ description &&
						<Form.Text>
							{ description }
						</Form.Text>
					}
				</>
			);
			break;
	}

	return field;
}
