import React from 'react';
import { Alert, Stack, Card } from "react-bootstrap";

import Field from "../../form/Field";

import { isEmpty, validate } from "../../../utils/conditionals";
import { objectToMappable } from "../../../utils/format";
import { createRefId } from "../../../utils/globals";

export default function FieldGroup( props ) {

	const {
		fields = null,
		value,
		onChange,
	} = props;

	if ( ! fields ) {
		return (
			<Alert variant="warning">No fields found.</Alert>
		)
	}

	const update = ( input, key ) => {
		let newValue = {...value};
		if ( ! isEmpty( input ) ) {
			newValue[ key ] = input;
		} else {
			// @todo Allow empty?
			delete newValue[ key ];
		}
		onChange( newValue );
	}

	return (
		<Stack gap={ 2 }>
			{
				objectToMappable( fields, 'name' ).map( ( field, index ) => {
					field = { ...field }; // Remove reference.

					if ( field.hasOwnProperty( 'conditionals' ) && ! validate( field.conditionals, value ) ) {
						return;
					}

					field.id = field.id ?? createRefId() + index;
					return (
						<Stack gap={ 0 }>
							<Field { ...field } value={ value[ field.name ] } onChange={ ( value ) => { update( value, field.name ) } }></Field>
							{ ( 'object' === typeof field.fields ) &&
								<Card className="bg-body-tertiary border border-top-0 p-1">
									<Card.Body className="bg-body p-3">
										<FieldGroup fields={ field.fields } onChange={ onChange } value={ value }></FieldGroup>
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
