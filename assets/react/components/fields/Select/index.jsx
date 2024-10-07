import React from 'react';
import { any, bool, func, object, string } from 'prop-types';
import SelectSimple from './Simple';
import SelectAdvanced from './Advanced';

export default function Select( props ) {

	if ( props.customizable ) {
		return <SelectSimple { ...props } />
	}

	return <SelectAdvanced { ...props } />
}

Select.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	label: string,
	icon: string,
	description: string,
	placeholder: string,
	attr: object,
	id: string,
	help: string|object,
	customizable: bool,
	prefix: any,
	postfix: any,
	choices: object,
	group: object,
	// Advanced.
	async: bool,
	onAsyncSearch: func,
	variant: string,
	filters: object,
	selectProps: object,
	compact: bool,
}
