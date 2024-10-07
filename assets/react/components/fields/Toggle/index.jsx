import React from 'react';
import { any, bool, func, object, string } from 'prop-types';
import ToggleSingle from './Single';
import ToggleMulti from './Multi';

export default function Toggle( props ) {

	if ( props.choices ) {
		return <ToggleMulti { ...props } />
	}

	return <ToggleSingle { ...props } />
}

Toggle.propTypes = {
	onChange: func,
	value: any,
	default: any,
	editable: bool,
	disabled: bool,
	readonly: bool,
	attr: object,
	id: string,
	label: string,
	help: string|object,
	type: string,
	button: bool,
	variant: string,
	toggle: object,
	choices: object,
	inline: bool,
	vertical: bool,
}
