import React, { useCallback } from 'react';
import { Stack, Card } from "react-bootstrap";

import Field from "../../form/Field";
import Fields from "./index";
import Group from './Group';
import TabGroup from "./TabGroup";

export default function FieldsItem( props ) {

	const {
		field,
		values,
		updateField,
	} = props;

	const updateNested = useCallback( ( value ) => {
		updateField( value, field.name, field )
	}, [ field ] );

	const update = useCallback( ( value, name, child ) => {
		updateField( value, name ?? field.name, child ?? field )
	}, [ field ] );

	let subComponents = null;

	if ( 'object' === typeof field.tabs ) {
		subComponents = <TabGroup tabs={ field.tabs } updateField={ updateField } values={ values } />
	} else if ( 'object' === typeof field.fields ) {
		subComponents = <Group fields={ field.fields } updateField={ updateField } values={ values } />
	} else if ( 'object' === typeof field.nested ) {
		subComponents = <Fields fields={ field.nested } value={ values[ field.name ] } onChange={ updateNested } />
	}

	let fieldComponent = null;
	if ( field.type ) {
		fieldComponent = <Field { ...field } value={ values[ field.name ] ?? field.default } values={ values } onChange={ update } />
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
				</Card>
		} else {
			items =
				<Card className="bg-body p-3">
					{ ! fieldComponent &&
					  <Field { ...field } type="title" />
					}
					{ subComponents }
				</Card>
		}

	} else if ( fieldComponent ) {
		items = fieldComponent;
	}

	if ( ! items ) {
		return;
	}

	return (
		<Stack gap={ 0 }>
			{ items }
		</Stack>
	)
}
