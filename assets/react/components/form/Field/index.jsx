import React from 'react';
import { Form, FloatingLabel, InputGroup } from "react-bootstrap";

import FieldContainer from './Container';
import Help from "../Help";

import Entity from "../../fields/Entity";
import Repeater from "../../fields/Repeater";
import Conditionals from "../../fields/Conditionals";
import Columns from '../../fields/Columns';
import Mapper from "../../fields/Mapper";
import Params from "../../fields/Params";
import Tasks from "../../fields/Tasks";
import Webservice from "../../fields/Webservice";
import Code from '../../fields/Code';

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

	let wrap = props.wrap ?? false;

	// Remove props that are not related to input fields.
	delete fieldProps.fields;
	delete fieldProps.choices;
	delete fieldProps.conditionals;
	delete fieldProps.tabs;
	delete fieldProps.tab;
	delete fieldProps.grid;
	delete fieldProps.config;
	delete fieldProps.wrap;
	delete fieldProps.collapsed;

	// Handle values manually.
	delete fieldProps.values;
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
				<div className="mb-2">
					<div><span>{ label }</span>{ help }</div>
					{ description }
				</div>
			)
			break;
		case 'entity':
			field = <Entity {...props} help={ help } />;
			break;
		case 'repeater':
			wrap  = true;
			field = <Repeater {...props} />;
			break;
		case 'conditionals':
			wrap  = true;
			field = <Conditionals {...props} />;
			break;
		case 'tasks':
			wrap  = true;
			field = <Tasks {...props} />;
			break;
		case 'webservice':
			wrap  = true;
			field = <Webservice {...props} />;
			break;
		case 'mapper':
			wrap  = true;
			field = <Mapper {...props} />;
			break;
		case 'params':
			wrap  = true;
			field = <Params {...props} />;
			break;
		case 'columns':
			wrap  = true;
			field = <Columns {...props} />;
			break;
		case 'code':
			wrap  = true;
			field = <Code { ...props } />;
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
							type={ ( 'switch' === type ) ? type : 'checkbox' }
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
			let choices = objectToMappable( props.choices ?? props.options ?? {}, 'value', 'label' );
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
								{ ! isEmpty( choices[0].value ) &&
									<option value="">{ props.selectLabel ?? '-- Select --' }</option>
								}
								{
									choices.map( ( option, index ) => {
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

	if ( wrap ) {
		return (
			<FieldContainer id={ id } label={ label } help={ help } description={ description } { ...props }>
				{ field }
			</FieldContainer>
		);
	}

	return field;
}
