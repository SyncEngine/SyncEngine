import React from 'react';
import PropTypes from 'prop-types';
import Fields from "../components/form/Fields";

export default function FieldsController( props ) {

	const {
		args = {},
		value,
		onChange
	} = props;

	const {
		fields,
	} = args;

	return (
		<Fields fields={ fields } value={ value } onChange={ onChange }></Fields>
	)
}

FieldsController.propTypes = {
	args: PropTypes.object,
	value: PropTypes.any,
	onChange: PropTypes.func,
};
