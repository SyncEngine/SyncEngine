import React from 'react';
import Stack from 'react-bootstrap/Stack';
import { objectToMappable } from "../utils/format";
import FieldController from "./FieldController";
import FieldsController from "./FieldsController";

export default function TaskController( props ) {

	const {
		value: value = {},
		name,
		label,
		description,
		onChange,
	} = props;

	if ( ! props.hasOwnProperty( 'fields' ) ) {
		return (
			<p>Task has no fields</p>
		)
	}

	return (
		<Stack gap={ 2 }>
			{ description }
			<FieldsController {...props} />
		</Stack>
	);
}
