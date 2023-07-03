import React from 'react';
import { Stack } from "react-bootstrap";
import FieldGroup from "../components/form/FieldGroup";
import { publish, subscribe } from "../utils/events";

export default function ConfigController( props ) {

	const {
		value,
		args = {},
		element,
		onChange,
	} = props;

	const {
		fields,
	} = args;

	const update = ( newValue ) => {
		onChange( newValue );

		publish( 'updateConfig', {
			id: element.closest( 'form' ).id,
			value: newValue
		} );
	}

	subscribe( 'requestConfig', ( e ) => {
		if ( element.closest( 'form' ).id === e.detail.id ) {
			publish( 'updateConfig', {
				id: element.closest( 'form' ).id,
				value: value
			} );
		}
	} );

	return (
		<Stack className="mt-2">
			<FieldGroup fields={ fields } value={ { ...value } } onChange={ update } />
		</Stack>
	)
}
