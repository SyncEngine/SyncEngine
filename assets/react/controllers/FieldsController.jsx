import React from 'react';
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
