import React, { useContext } from 'react';
import { createRefId } from '../../../utils/globals';

// Field elements.
import FieldContainer from './Container';
import Help from '../Help';
import Description from '../Description';

// Basic fields.
import Toggle from '../../fields/Toggle';
import Radio from '../../fields/Radio';
import Select from '../../fields/Select';
import Input from '../../fields/Input';

// Advanced fields.
import Entity from '../../fields/Entity';
import Entities from '../../fields/Entities';
import Repeater from '../../fields/Repeater';
import Grid from '../../fields/Grid';
import Conditions from '../../fields/Conditions';
import Mapper from '../../fields/Mapper';
import Params from '../../fields/Params';
import Code from '../../fields/Code';
import Column from '../../fields/Column';
import Schema from '../../fields/Schema';
import Tasks from '../../fields/Tasks';
import Webservice from '../../fields/Webservice';
import Authentication from '../../fields/Authentication';
import Secret from '../../fields/Secret';

import { FieldsContext } from '../../../context/FieldsContext';

export default function Field( props ) {

	const {
		label = props.name,
		type,
	} = props;

	const refContext = useContext( FieldsContext );
	const id = ( refContext.id && refContext.id + '_' ) + ( props.id ?? createRefId() ); // Make sure ID is always unique so labels will work correctly.

	let field, wrap = false;

	switch ( type ) {
		// UI Elements.
		case 'separator':
			field = (
				props.line ?
					<hr className={ props.size && 'my-' + props.size } />
						:
					<div className={ props.size && 'py-' + props.size } />
			);
			break;
		case 'title':
			field = (
				<div className="mb-2">
					<div><span>{ label }</span>{ props.help && <Help text={ props.help } id={ id } /> }</div>
					{ props.description && <Description text={ props.description } id={ id } /> }
				</div>
			)
			break;

		// Advanced fields.
		case 'entity':
			field = <Entity { ...props } id={ id } />;
			break;
		case 'entities':
			wrap  = true;
			field = <Entities { ...props } id={ id } />;
			break;
		case 'repeater':
			wrap  = true;
			field = <Repeater { ...props } id={ id } />;
			break;
		case 'grid':
			wrap  = true;
			field = <Grid { ...props } id={ id } />;
			break;
		case 'conditions':
			wrap  = true;
			field = <Conditions { ...props } id={ id } />;
			break;
		case 'column':
			wrap  = false;
			field = <Column { ...props } id={ id } />;
			break;
		case 'schema':
			wrap  = true;
			field = <Schema { ...props } id={ id } />;
			break;
		case 'tasks':
			wrap  = true;
			field = <Tasks { ...props } id={ id } />;
			break;
		case 'webservice':
			wrap  = true;
			field = <Webservice { ...props } id={ id } />;
			break;
		case 'authentication':
			wrap  = true;
			field = <Authentication { ...props } id={ id } />;
			break;
		case 'mapper':
			wrap  = true;
			field = <Mapper { ...props } id={ id } />;
			break;
		case 'params':
			wrap  = true;
			field = <Params { ...props } id={ id } />;
			break;
		case 'code':
			wrap  = true;
			field = <Code { ...props } id={ id } />;
			break;
		case 'secret':
			field = <Secret { ...props } id={ id } />;
			break;

		// Basic fields.
		case 'boolean':
		case 'checkbox':
		case 'switch':
		case 'toggle':
			field = <Toggle { ...props } id={ id } />;
			break;
		case 'radio':
			field = <Radio { ...props } id={ id } />;
			break;
		case 'select':
			field = <Select { ...props } id={ id } />;
			break;
		case 'textarea':
			field = <Input { ...props } id={ id } multiline={ true } />
			break;
		default:
			// @todo custom field types?
			field = <Input { ...props } id={ id } multiline={ false } />
			break;
	}

	if ( props.wrap ?? wrap ) {
		if ( ! wrap ) {
			return (
				<FieldContainer
					{ ...props }
					id={ id }
					label={ null }
					help={ null }
					description={ null }
				>
					{ field }
				</FieldContainer>
			)
		}

		return (
			<FieldContainer
				{ ...props }
				id={ id }
				label={ label }
				help={ props.help && <Help text={ props.help } id={ id } /> }
				description={ props.description && <Description text={ props.description } id={ id } /> }
			>
				{ field }
			</FieldContainer>
		);
	}

	return field;
}
