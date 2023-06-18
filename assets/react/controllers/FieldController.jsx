import React from 'react';
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
