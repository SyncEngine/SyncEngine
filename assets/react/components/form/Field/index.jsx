import React, { useCallback } from 'react';
import { createRefId } from "../../../utils/globals";

// Field elements.
import FieldContainer from './Container';
import Help from "../Help";
import Description from '../Description';

// Basic fields.
import Toggle from '../../fields/Toggle';
import Radio from '../../fields/Radio';
import Select from '../../fields/Select';
import Text from '../../fields/Text';

// Advanced fields.
import Entity from "../../fields/Entity";
import Entities from '../../fields/Entities';
import Repeater from "../../fields/Repeater";
import Conditionals from "../../fields/Conditionals";
import Columns from '../../fields/Columns';
import Mapper from "../../fields/Mapper";
import Params from "../../fields/Params";
import Tasks from "../../fields/Tasks";
import Webservice from "../../fields/Webservice";
import Code from '../../fields/Code';

export default function Field( props ) {

	const {
		id: id = createRefId(),
		type,
		label: label = props.name,
		onChange,
	} = props;

	let wrap = false;

	let field;
	let fieldProps = { ...props };

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

	const handleChange = useCallback( ( e ) => {
		onChange( e.target.value );
	}, [ onChange, id, props.name ] );

	switch ( type ) {
		case 'separator':
			break;
		case 'title':
			field = (
				<div className="mb-2">
					<div><span>{ label }</span>{ props.help && <Help text={ props.help } id={ id } /> }</div>
					{ props.description && <Description text={ props.description } id={ id } /> }
				</div>
			)
			break;
		case 'entity':
			field = <Entity { ...props } />;
			break;
		case 'entities':
			field = <Entities { ...props } />;
			break;
		case 'repeater':
			wrap  = true;
			field = <Repeater { ...props } />;
			break;
		case 'conditionals':
			wrap  = true;
			field = <Conditionals { ...props } />;
			break;
		case 'tasks':
			wrap  = true;
			field = <Tasks { ...props } />;
			break;
		case 'webservice':
			wrap  = true;
			field = <Webservice { ...props } />;
			break;
		case 'mapper':
			wrap  = true;
			field = <Mapper { ...props } />;
			break;
		case 'params':
			wrap  = true;
			field = <Params { ...props } />;
			break;
		case 'columns':
			wrap  = true;
			field = <Columns { ...props } />;
			break;
		case 'code':
			wrap  = true;
			field = <Code { ...props } />;
			break;
		case 'boolean':
		case 'checkbox':
		case 'switch':
		case 'toggle':
			field = <Toggle { ...props } attr={ fieldProps } />;
			break;
		case 'radio':
			field = <Radio { ...props } attr={ fieldProps } />;
			break;
		case 'select':
			field = <Select { ...props } attr={ fieldProps } />;
			break;
		case 'textarea':
			field = <Text { ...props } attr={ fieldProps } multiline={ true } />
			break;
		default:
			// @todo custom field types?
			field = <Text { ...props } attr={ fieldProps } multiline={ false } />
			break;
	}

	if ( props.wrap ?? wrap ) {
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
