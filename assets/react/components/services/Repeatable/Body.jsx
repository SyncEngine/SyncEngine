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

	// @todo Force label and desc as first, even if field keys are numeric.

	return (
		<Stack gap={ 2 } className={ ( ( props._disabled ?? false ) ? 'opacity-50' : '' ) }>
			{ description }
			<Fields {...props} fields={ { _label: labelField, _description: descriptionField, ...props.fields } } />
		</Stack>
	);
}
