import React from 'react';
import { Alert, Stack } from "react-bootstrap";
import FieldGroup from "../../form/FieldGroup";

export default function Task( props ) {

	const {
		value = {},
		label,
		description,
	} = props;

	if ( ! props.hasOwnProperty( 'fields' ) ) {
		return (
			<Alert variant="warning">Task has no fields</Alert>
		)
	}

	const labelField = {
		name: 'label',
		label: 'Label',
		type: 'text',
		value: value.label ?? label,
	}

	const descriptionField = {
		name: 'description',
		label: 'Description',
		type: 'text',
		value: value.description ?? description,
	}

	return (
		<Stack gap={ 2 }>
			{ description }
			<FieldGroup {...props} fields={ { labelField, descriptionField, ...props.fields } } />
		</Stack>
	);
}
