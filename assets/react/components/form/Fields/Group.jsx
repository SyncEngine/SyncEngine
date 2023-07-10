import React from 'react';
import { Alert, Stack, Card } from "react-bootstrap";

import Field from "../../form/Field";
import Fields from "./index";

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

	let elements = objectToMappable( fields, 'name' ).map( ( field, index ) => {
		field = { ...field }; // Remove reference.

		field.id = field.id ?? createRefId() + index;

		if ( field.hasOwnProperty( 'conditionals' ) && ! validate( field.conditionals, values ) ) {
			return;
		}

		let subComponents = null;
		if ( 'object' === typeof field.tabs ) {
			subComponents = (
				<TabGroup tabs={ field.tabs } updateField={ updateField } values={ values } />
			);
		} else if ( 'object' === typeof field.fields ) {
			subComponents = (
				<Group fields={ field.fields } updateField={ updateField } values={ values } />
			);
		} else if ( 'object' === typeof field.nested ) {
			subComponents = (
				<Fields fields={ field.nested } value={ values[ field.name ] } onChange={ ( value ) => { updateField( value, field.name ) } } />
			);
		}

		let fieldComponent = null;
		if ( field.type ) {
			fieldComponent = (
				<Field { ...field } value={ values[ field.name ] ?? field.default } onChange={ ( value ) => { updateField( value, field.name ) } } />
			)
		}

		return (
			<Stack key={ index } gap={ 0 }>
				{ fieldComponent }
				{ ( subComponents && ! fieldComponent && ! field.label ) &&
					subComponents
				}
				{ ( subComponents && ( fieldComponent || field.label ) ) &&
					<Card className={ "bg-body-tertiary border p-1" + ( fieldComponent ? ' border-top-0' : '' ) }>
						<Card.Body className="bg-body p-3">
							{ ! fieldComponent &&
								<Field { ...field } type="title" />
							}
							{ subComponents }
						</Card.Body>
					</Card>
				}
			</Stack>
		)
	} ).filter( ( elem ) => React.isValidElement( elem ) );

	if ( ! elements.length ) {
		return;
	}

	return (
		<Stack gap={ 2 }>
			{ elements }
		</Stack>
	);
}
