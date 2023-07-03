import React from 'react';
import { Alert, Stack, Card } from "react-bootstrap";

import Field from "../../form/Field";

import { validate } from "../../../utils/conditionals";
import { objectToMappable } from "../../../utils/format";
import { createRefId } from "../../../utils/globals";

export default function Group( props ) {

	const {
		fields,
		values,
		updateField,
	} = props;

	if ( ! fields ) {
		return (
			<Alert variant="warning">No fields found.</Alert>
		)
	}

	return (
		<Stack gap={ 2 }>
			{
				objectToMappable( fields, 'name' ).map( ( field, index ) => {
					field = { ...field }; // Remove reference.

					if ( field.hasOwnProperty( 'conditionals' ) && ! validate( field.conditionals, values ) ) {
						return;
					}

					field.id = field.id ?? createRefId() + index;
					return (
						<Stack key={ index } gap={ 0 }>
							<Field { ...field } value={ values[ field.name ] } onChange={ ( value ) => { updateField( value, field.name ) } }></Field>
							{ ( 'object' === typeof field.fields ) &&
							  <Card className="bg-body-tertiary border border-top-0 p-1">
								  <Card.Body className="bg-body p-3">
									  <Group fields={ field.fields } updateField={ updateField } values={ values }></Group>
								  </Card.Body>
							  </Card>
							}
						</Stack>
					)
				} )
			}
		</Stack>
	);
}
