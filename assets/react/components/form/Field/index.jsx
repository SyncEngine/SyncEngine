import React from 'react';
import { Card, Form, FloatingLabel, InputGroup } from "react-bootstrap";

import Mapper from "../../fields/Mapper";
import Params from "../../fields/Params";
import Conditions from "../../fields/Conditions";
import Entity from "../../fields/Entity";
import Help from "../Help";

import { objectToMappable } from "../../../utils/format";
import { createRefId } from "../../../utils/globals";

export default function Field( props ) {

	const {
		id: id = createRefId(),
		type,
		label: label = props.name,
		onChange,
	} = props;

	let field;

	const help = props.help && (
         <Help id={ 'help' + id } text={ props.help } />
	)

	const description = props.description && (
		<Form.Text id={ 'desc' + id } muted>
			{ props.description }
		</Form.Text>
	)

	switch ( type ) {
		case 'conditions':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1 mb-1"><span className="text-secondary">{ label }</span>{ help }</div>
						{ description }
						<Conditions {...props} />
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
						{...props}
						onChange={ ( event ) => { onChange( event.target.checked ) } }
						label={ <><span className="text-secondary">{ label }</span>{ help }</> }
						checked={ props.value }
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
						{...props}
						onChange={ ( event ) => { onChange( event.target.value ) } }
						label={ <><span className="text-secondary">{ label }</span>{ help }</> }
						checked={ props.value }
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
								{...props}
								placeholder={ props.placeholder ?? ' ' }
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
