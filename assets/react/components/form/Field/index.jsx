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

	// Handle values manually.
	delete fieldProps.value;
	delete fieldProps.default;

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

	const handleCheck = ( e ) => {
		onChange( e.target.checked );
	}

	const handleMultiCheck = ( e ) => {
		let value = props.value;
		if ( ! value || 'object' !== typeof value ) {
			value = {};
		}
		if ( e.target.checked ) {
			value[ e.target.value ] = true;
		} else {
			delete value[ e.target.value ];
		}
		onChange( value );
	}

	const handleChange = ( e ) => {
		onChange( e.target.value );
	}

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
		case 'switch':
			if ( props.choices ) {
				field = (
					<div>
						<div className="mt-n1 mb-1"><span className="text-secondary">{ label }</span>{ help }</div>
						{ description }
						{
							objectToMappable( props.choices ?? {}, 'value', 'label' ).map( ( option, index ) => {
								return <Form.Check
									id={ fieldProps.id + option.value }
									key={ option.value }
									value={ option.value }
									onChange={ handleMultiCheck }
									label={ option.label }
									checked={ ( props.value ? props.value[ option.value ] : props.default && props.default[ option.value ] ) ?? false }
									type={ ( 'switch' === type ) ? type : 'checkbox' }
									inline={ ! isEmpty( props.inline ) }
								/>;
							} )
						}
					</div>
				);
			} else {
				field = (
					<div>
						<Form.Check
							{...fieldProps}
							onChange={ handleCheck }
							label={ <><span className="text-secondary">{ label }</span>{ help }</> }
							checked={ ! isEmpty( props.value ?? props.default ) }
							type="checkbox"
						/>
						{ description }
					</div>
				);
			}
			break;
		case 'radio':
			field = (
				<div>
					<div className="mt-n1 mb-1"><span className="text-secondary">{ label }</span>{ help }</div>
					{ description }
					{
						objectToMappable( props.choices ?? {}, 'value', 'label' ).map( ( option, index ) => {
							return <Form.Check
								id={ fieldProps.id + option.value }
								key={ option.value }
								value={ option.value }
								onChange={ handleChange }
								label={ option.label }
								checked={ props.value ? props.value === option.value : props.default === option.value }
								type={ 'radio' }
								inline={ ! isEmpty( props.inline ) }
							/>;
						} )
					}
				</div>
			);
			break;
		case 'select':
			// @todo deprecate options support?
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
								value={ props.value ?? props.default ?? '' }
								onChange={ handleChange }
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
								value={ props.value ?? props.default ?? '' }
								onChange={ handleChange }
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
