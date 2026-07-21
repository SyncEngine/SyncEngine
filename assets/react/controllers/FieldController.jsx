import React from 'react';
import PropTypes from 'prop-types';
import Field from "../components/form/Field";

export default function FieldController( props ) {

	const {
		args = {},
		value,
		onChange,
	} = props;

	return (
		<Field { ...args } value={ value } onChange={ onChange }></Field>
	)
}

FieldController.propTypes = {
	args: PropTypes.object,
	value: PropTypes.any,
	onChange: PropTypes.func,
};
