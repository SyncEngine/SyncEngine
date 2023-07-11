import React from 'react';
import { Card, Form, FloatingLabel, InputGroup } from "react-bootstrap";

import Repeater from "../../fields/Repeater";
import Columns from '../../fields/Columns';
import Mapper from "../../fields/Mapper";
import Params from "../../fields/Params";
import Conditionals from "../../fields/Conditionals";
import Tasks from "../../fields/Tasks";
import Entity from "../../fields/Entity";
import Code from '../../fields/Code';
import Help from "../Help";

import { objectToMappable } from "../../../utils/data";
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
		<Help id={ 'help_' + id } text={ props.help } />
	)

	const description = props.description && (
		<Form.Text id={ 'desc_' + id } muted>
			{ props.description }
		</Form.Text>
	)

	const handleCheck = ( e ) => {
		onChange( e.target.checked );
	}

	const handleChange = ( e ) => {
		onChange( e.target.value );
	}

	switch ( type ) {
		case 'separator':
			break;
		case 'title':
			field = (
				<div className="mb-1">
					<div><span>{ label }</span>{ help }</div>
					{ description }
				</div>
			)
			break;
		case 'repeater':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1 mb-1"><span>{ label }</span>{ help }</div>
						{ description }
						<Repeater {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'conditionals':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1 mb-1"><span>{ label }</span>{ help }</div>
						{ description }
						<Conditionals {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'tasks':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1 mb-1"><span>{ label }</span>{ help }</div>
						{ description }
						<Tasks {...props} />
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
						<div className="mt-n1 mb-1"><span>{ label }</span>{ help }</div>
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
						<div className="mt-n1 mb-1"><span>{ label }</span>{ help }</div>
						{ description }
						<Params {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'columns':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1 mb-1"><span>{ label }</span>{ help }</div>
						{ description }
						<Columns {...props} />
					</Card.Body>
				</Card>
			);
			break;
		case 'code':
			field = (
				<Card>
					<Card.Body>
						<div className="mt-n1 mb-1"><span>{ label }</span>{ help }</div>
						{ description }
						<Code { ...props } />
					</Card.Body>
				</Card>
			)
			break;
		case 'boolean':
		case 'checkbox':
		case 'switch':
			if ( props.choices ) {

				const handleMultiCheck = ( e ) => {
					let value = props.value;
					if ( ! value || 'object' !== typeof value ) {
						value = [];
					}
					if ( e.target.checked ) {
						value.push( e.target.value );
					} else {
						let index = value.indexOf( e.target.value );
						if ( -1 !== index ) {
							value.splice( index, 1 );
						}
					}
					onChange( value );
				}

				const isChecked = ( value, props ) => {
					if ( ! isEmpty( value ) ) {
						if ( props.value ) {
							if ( Array.isArray( props.value ) ) {
								return props.value.includes( value );
							}
							return props.value === value;
						}
						if ( props.default ) {
							if ( Array.isArray( props.default ) ) {
								return props.default.includes( value );
							}
							return props.default === value;
						}
					}
					return false;
				};

				field = (
					<div>
						<div className="mb-1"><span>{ label }</span>{ help }</div>
						{ description }
						{
							objectToMappable( props.choices ?? {}, 'value', 'label' ).map( ( option, index ) => {
								return <Form.Check
									id={ fieldProps.id + option.value }
									key={ option.value }
									value={ option.value }
									onChange={ handleMultiCheck }
									label={ option.label }
									checked={ isChecked( option.value, props ) }
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
							label={ <><span>{ label }</span>{ help }</> }
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
					<div className="mt-n1 mb-1"><span>{ label }</span>{ help }</div>
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
				<div className="flex-grow-1">
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
		case 'textarea':
			// @todo custom field types?
			field = (
				<div className="flex-grow-1">
					<InputGroup>
						{ props.help &&
						  <Help id={ 'help' + id } text={ props.help } inputGroup={ true } />
						}
						<FloatingLabel label={ label }>
							<Form.Control
								{...fieldProps}
								as="textarea"
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
		default:
			// @todo custom field types?
			field = (
				<div className="flex-grow-1">
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
