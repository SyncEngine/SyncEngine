import React from 'react';
import { Alert, Stack } from "react-bootstrap";
import Fields from "../../form/Fields";

export default function Body( props ) {

	const {
		value = {},
		label,
		description,
	} = props;

	if ( ! props.hasOwnProperty( 'fields' ) ) {
		return (
			<Alert variant="warning">No fields found.</Alert>
		)
	}

	const labelField = {
		name: '_label',
		label: 'Label',
		type: 'text',
		value: value._label ?? label,
	}

	const descriptionField = {
		name: '_description',
		label: 'Description',
		type: 'text',
		value: value._description ?? description,
	}

	return (
		<Stack gap={ 2 }>
			{ description }
			<Fields {...props} fields={ { labelField, descriptionField, ...props.fields } } />
		</Stack>
	);
}
