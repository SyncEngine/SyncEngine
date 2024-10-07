import React from 'react';
import { any, array, bool, func, object, oneOfType, string } from 'prop-types';
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
	attr: oneOfType( [ object, array ] ),
	id: string,
	label: oneOfType( [ string, object ] ),
	help: oneOfType( [ string, object, array ] ),
	type: string,
	button: oneOfType( [ bool, string, object ] ),
	variant: string,
	toggle: oneOfType( [ object, array ] ),
	choices: oneOfType( [ object, array ] ),
	inline: bool,
	vertical: bool,
}
