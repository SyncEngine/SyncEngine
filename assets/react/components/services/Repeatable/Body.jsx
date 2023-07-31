import React from 'react';
import { Alert, Stack } from "react-bootstrap";
import Fields from "../../form/Fields";

export default function Body( props ) {

	const {
		value = {},
		description,
	} = props;

	if ( ! props.hasOwnProperty( 'fields' ) ) {
		return <Alert variant="warning">No fields found.</Alert>
	}

	const fields = {
		id: props._ref + '_',
		inline: true,
		fields: [
			{
				id: props._ref + '_label',
				name: '_label',
				label: 'Label',
				type: 'text',
				value: value._label ?? '',
			},
			{
				id: props._ref + '_description',
				name: '_description',
				label: 'Description',
				type: 'text',
				value: value._description ?? '',
			}
		]
	}

	// @todo Force label and desc as first, even if field keys are numeric.

	return (
		<Stack gap={ 2 } className={ ( ( props._disabled ?? false ) ? 'opacity-50' : '' ) }>
			{ description }
			<Fields {...props} fields={ { _: fields, ...props.fields } } />
		</Stack>
	);
}
