import React from 'react';
import { Alert, Stack, Card } from "react-bootstrap";

import Field from "../../form/Field";
import Fields from "./index";
import TabGroup from "./TabGroup";

import { validate } from "../../../utils/conditionals";
import { objectToMappable } from "../../../utils/data";
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

	let elements = objectToMappable( fields, 'name' ).filter( ( field => {
		return ! (
			field.hasOwnProperty( 'conditionals' ) && !validate( field.conditionals, values )
		);
	} ) ).map( ( field, index ) => {
		field = { ...field }; // Remove reference.

		field.id = field.id ?? createRefId() + index;

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
				<Fields fields={ field.nested } value={ values[ field.name ] } onChange={ ( value ) => { updateField( value, field.name, field ) } } />
			);
		}

		let fieldComponent = null;
		if ( field.type ) {
			fieldComponent = (
				<Field { ...field } value={ values[ field.name ] ?? field.default } values={ values } onChange={ ( value, name, child ) => { updateField( value, name ?? field.name, child ?? field ) } } />
			)
		}

		let items = null;
		if ( subComponents ) {
			if ( ! fieldComponent && ! field.label ) {
				items = subComponents;
			} else if ( fieldComponent ) {
				const isSwitch = -1 < [ 'checkbox', 'switch' ].indexOf( field.type );
				items =
					<Card className="bg-body border-0">
						<Card.Header className={ "bg-body " + ( isSwitch ? 'p-3 border' : ' p-0 border-0' ) }>
							{ fieldComponent }
						</Card.Header>
						<Card.Body className="border p-3">
							{ subComponents }
						</Card.Body>
					</Card>;
			} else {
				items =
					<Card className="bg-body p-3">
						{ ! fieldComponent &&
							<Field { ...field } type="title" />
						}
						{ subComponents }
					</Card>;
			}
		} else if ( fieldComponent ) {
			items = fieldComponent;
		}

		if ( ! items ) {
			return;
		}

		return (
			<Stack key={ index } gap={ 0 }>
				{ items }
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
