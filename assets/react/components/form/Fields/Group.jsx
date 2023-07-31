import React from 'react';
import { Alert, Stack } from 'react-bootstrap';

import { validate } from "../../../utils/conditionals";
import { objectToMappable } from "../../../utils/data";
import FieldsItem from './Item';
import { createRefId } from '../../../utils/globals';

export default function Group( props ) {

	const {
		fields,
		values,
		updateField,
		inline = false,
	} = props;

	if ( ! fields ) {
		return <Alert variant="warning">No fields found.</Alert>
	}

	let elements = objectToMappable( fields, 'name' ).filter( ( field => {
		return ! (
			field.hasOwnProperty( 'conditionals' ) && !validate( field.conditionals, values )
		);
	} ) ).map( ( field, index ) => {

		field.id = field.id ?? createRefId();

		return <FieldsItem key={ field.id } updateField={ updateField } field={ field } values={ values } />;

	} ).filter( ( elem ) => React.isValidElement( elem ) );

	if ( ! elements.length ) {
		return;
	}

	return (
		<Stack gap={ 2 } direction={ inline ? 'horizontal' : 'vertical' }>
			{ elements }
		</Stack>
	);
}
