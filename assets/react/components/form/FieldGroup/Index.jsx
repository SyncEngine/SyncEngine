import React, { useEffect, useState } from 'react';
import { Alert, Stack } from "react-bootstrap";

import Conditional from "../../services/Conditional";
import Field from "../../form/Field";

import { isEmpty } from "../../../utils/conditionals";
import { objectToMappable } from "../../../utils/format";
import { createRefId } from "../../../utils/globals";
import Card from "react-bootstrap/Card";

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
					const fieldValue = value[ field.name ] ?? '';
					field.id = field.id ?? createRefId() + index;

					return (
						<Conditional key={ index } { ...field } data={ value } value={ fieldValue } >
							<Stack gap={ 0 }>
								<Field { ...field } value={ fieldValue } onChange={ ( value ) => { update( value, field.name ) } }></Field>
								{ ( 'object' === typeof field.fields ) &&
									<Card className="bg-body-tertiary border-top-0 p-2">
										<Card.Body className="bg-body p-3">
											<FieldGroup fields={ field.fields } onChange={ onChange } value={ value }></FieldGroup>
										</Card.Body>
									</Card>
								}
							</Stack>
						</Conditional>
					)
				} )
			}
		</Stack>
	);
}
