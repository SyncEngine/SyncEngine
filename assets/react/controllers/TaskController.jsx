import React from 'react';
import { Alert, Stack } from "react-bootstrap";
import FieldsController from "./FieldsController";

export default function TaskController( props ) {

	const {
		value: value = {},
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
		value: value.label ?? label,
	}

	const descriptionField = {
		name: 'description',
		label: 'Description',
		value: value.description ?? description,
	}

	return (
		<Stack gap={ 2 }>
			{ description }
			<FieldsController {...props} fields={ { labelField, descriptionField, ...props.fields } } />
		</Stack>
	);
}
