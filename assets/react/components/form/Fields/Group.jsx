import React from 'react';
import { Alert, Stack, Card } from "react-bootstrap";

import Field from "../../form/Field";

import { validate } from "../../../utils/conditionals";
import { objectToMappable } from "../../../utils/format";
import { createRefId } from "../../../utils/globals";
import TabGroup from "./TabGroup";

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

					let subs = null;
					if ( 'object' === typeof field.tabs ) {
						subs = (
							<TabGroup tabs={ field.tabs } updateField={ updateField } values={ values } />
						);
					} else if ( 'object' === typeof field.fields ) {
						subs = (
							<Group fields={ field.fields } updateField={ updateField } values={ values } />
						);
					}

					field.id = field.id ?? createRefId() + index;
					return (
						<Stack key={ index } gap={ 0 }>
							{ field.type &&
								<Field { ...field } value={ values[ field.name ] } onChange={ ( value ) => { updateField( value, field.name ) } } />
							}
							{ ( subs && ! field.type && ! field.label ) &&
								subs
							}
							{ ( subs && ( field.type || field.label ) ) &&
								<Card className="bg-body-tertiary border border-top-0 p-1">
									<Card.Body className="bg-body p-3">
										{ ! field.type &&
											<Field { ...field } type="title" />
										}
										{ subs }
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
