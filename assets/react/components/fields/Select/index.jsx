import React from 'react';
import { any, array, bool, func, object, oneOfType, string } from 'prop-types';
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
	label: oneOfType( [ string, object ] ),
	icon: string,
	description: string,
	placeholder: string,
	attr: oneOfType( [ object, array ] ),
	id: string,
	help: oneOfType( [ string, object ] ),
	customizable: bool,
	prefix: any,
	postfix: any,
	choices: oneOfType( [ object, array ] ),
	group: oneOfType( [ object, array ] ),
	// Advanced.
	async: bool,
	onAsyncSearch: func,
	variant: string,
	filters: oneOfType( [ object, array ] ),
	selectProps: oneOfType( [ object, array ] ),
	compact: bool,
}
