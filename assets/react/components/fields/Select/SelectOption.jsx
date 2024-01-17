import React from 'react';

export default function SelectOption( props ) {
	let {
		label,
		name,
		type,
		value
	} = props;

	value = value ?? type ?? name;
	if ( ! label ) {
		label = name ?? value;
	}

	if ( props.description ) {
		label += ' - ' + props.description + '';
	}

	return ( <option value={ value }>{ label }</option> );
}
