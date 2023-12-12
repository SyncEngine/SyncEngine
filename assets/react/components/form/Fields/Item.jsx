import React, { useCallback } from 'react';
import { Stack, Card } from 'react-bootstrap';

import Field from "../../form/Field";
import Fields from "./index";
import Group from './Group';
import Tabs from "../Tabs";
import FieldContainer from '../Field/Container';

export default function FieldsItem( props ) {

	const {
		field,
		values,
		updateField,
		wrap,
	} = props;

	const updateNested = useCallback( ( value ) => {
		updateField( value, field.name, field )
	}, [ field.id, field.name, updateField ] );

	const update = useCallback( ( value, name, child ) => {
		updateField( value, name ?? field.name, child ?? field )
	}, [ field.id, field.name, updateField ] );

	let subComponents = null;
	switch ( true ) {
		case 'object' === typeof field.tabs:
			subComponents = <Tabs tabs={ field.tabs } updateField={ updateField } values={ values } />
			break;
		case 'object' === typeof field.fields:
			subComponents = <Group fields={ field.fields } updateField={ updateField } values={ values } inline={ field.inline } />
			break;
		case 'object' === typeof field.nested:
			subComponents = <Fields fields={ field.nested } value={ values[ field.name ] } onChange={ updateNested } />
			break;
	}

	let fieldComponent = null;
	if ( field.type ) {
		fieldComponent = <Field wrap={ wrap } { ...field } value={ values[ field.name ] ?? field.default } values={ values } onChange={ update } />
	}

	let items = null;
	if ( subComponents ) {

		if ( fieldComponent ) {
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
			items = field.label ? <FieldContainer { ...field }>{ subComponents }</FieldContainer> : subComponents;
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
