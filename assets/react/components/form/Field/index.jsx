import React from 'react';
import { Card, Form, FloatingLabel, InputGroup } from "react-bootstrap";

import Mapper from "../../fields/Mapper";
import Params from "../../fields/Params";
import Conditionals from "../../fields/Conditionals";
import Entity from "../../fields/Entity";
import Help from "../Help";

import { objectToMappable } from "../../../utils/format";
import { createRefId } from "../../../utils/globals";
import { isEmpty } from "../../../utils/conditionals";

export default function Field( props ) {

	const {
		id: id = createRefId(),
		type,
		label: label = props.name,
		onChange,
	} = props;

	let field;
	let fieldProps = { ...props };

	// Remove props that are not related to input fields.
	delete fieldProps.fields;
	delete fieldProps.choices;
	delete fieldProps.conditionals;
	delete fieldProps.config;

	fieldProps.value = props.value ?? props.default;

	// Do not pass React fields into form serializer. Prevents unwanted post form data.
	delete fieldProps.name;

	const help = props.help && (
         <Help id={ 'help' + id } text={ props.help } />
	)

	const description = props.description && (
		<Form.Text id={ 'desc' + id } muted>
			{ props.description }
		</Form.Text>
	)

	switch ( type ) {
		case 'conditionals':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1 mb-1"><span className="text-secondary">{ label }</span>{ help }</div>
						{ description }
						<Conditionals {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'entity':
			field = (
				<Entity {...props} help={ help } />
			);
			break;
		case 'mapper':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1 mb-1"><span className="text-secondary">{ label }</span>{ help }</div>
						{ description }
						<Mapper {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'params':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1 mb-1"><span className="text-secondary">{ label }</span>{ help }</div>
						{ description }
						<Params {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'boolean':
		case 'checkbox':
			field = (
				<div>
					<Form.Check
						{...fieldProps}
						onChange={ ( event ) => { onChange( event.target.checked ) } }
						label={ <><span className="text-secondary">{ label }</span>{ help }</> }
						checked={ ! isEmpty( fieldProps.value ) }
						type="checkbox"
					/>
					{ description }
				</div>
			);
			break;
		case 'radio':
			// @todo multiple options.
			field = (
				<div>
					<Form.Check
						{...fieldProps}
						onChange={ ( event ) => { onChange( event.target.value ) } }
						label={ <><span className="text-secondary">{ label }</span>{ help }</> }
						checked={ ! isEmpty( fieldProps.value ) }
						type="radio"
					/>
					{ description }
				</div>
			);
			break;
		case 'select':
			let choices = props.choices ?? props.options ?? {};
			field = (
				<div>
					<InputGroup>
						{ props.help &&
							<Help id={ 'help' + id } text={ props.help } inputGroup={ true } />
						}
						<FloatingLabel label={ label }>
							<Form.Select
								{...fieldProps}
								label={ label }
								placeholder={ props.placeholder ?? props.label }
								value={ fieldProps.value ?? '' }
								onChange={ ( event ) => { onChange( event.target.value ) } }
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
					{ description }
				</div>
			);
			break;
		default:
			// @todo custom field types?
			field = (
				<div>
					<InputGroup>
						{ props.help &&
							<Help id={ 'help' + id } text={ props.help } inputGroup={ true } />
						}
						<FloatingLabel label={ label }>
							<Form.Control
								{...fieldProps}
								placeholder={ props.placeholder ?? ' ' }
								value={ fieldProps.value ?? '' }
								onChange={ ( event ) => { onChange( event.target.value ) } }
							/>
						</FloatingLabel>
					</InputGroup>
					{ description }
				</div>
			);
			break;
	}

	return field;
}
