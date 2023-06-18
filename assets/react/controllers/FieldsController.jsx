import React from 'react';
import FieldGroup from "../components/form/FieldGroup";

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
		<FieldGroup fields={ fields } value={ value } onChange={ onChange }></FieldGroup>
	)
}
