import React, { useState } from 'react';
import { Alert, Stack, Card } from "react-bootstrap";

import Field from "../../form/Field";

import SubGroup from "./Subgroup";
import TabGroup from "./TabGroup";

import { isEmpty, validate } from "../../../utils/conditionals";
import { objectToMappable } from "../../../utils/format";
import { createRefId } from "../../../utils/globals";

export default function Fields( props ) {

	const {
		fields = null,
		value,
		onChange,
	} = props;

	const [ values, setValues ] = useState( value );

	if ( ! fields ) {
		return (
			<Alert variant="warning">No fields found.</Alert>
		)
	}

	const updateField = ( input, key ) => {
		let newValues = { ...values };
		if ( ! isEmpty( input ) ) {
			newValues[ key ] = input;
		} else {
			// @todo Allow empty?
			delete newValues[ key ];
		}
		setValues( newValues );
		onChange( newValues );
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
							{ field.type &&
								<Field { ...field } value={ values[ field.name ] } onChange={ ( value ) => { updateField( value, field.name ) } }></Field>
							}
							{ ( 'object' === typeof field.tabs ) &&
								<TabGroup tabs={ field.tabs } updateField={ updateField } values={ values }></TabGroup>
							}
							{ ( 'object' === typeof field.fields ) &&
								<Card className="bg-body-tertiary border border-top-0 p-1">
									<Card.Body className="bg-body p-3">
										<SubGroup fields={ field.fields } updateField={ updateField } values={ values }></SubGroup>
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
