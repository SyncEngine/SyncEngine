import React from 'react';
import PropTypes from 'prop-types';

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

SelectOption.propTypes = {
	label: PropTypes.string,
	name: PropTypes.string,
	type: PropTypes.string,
	value: PropTypes.any,
	description: PropTypes.string,
};
