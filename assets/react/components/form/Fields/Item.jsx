import React, { useRef } from 'react';
import { Card } from 'react-bootstrap';

import Field from '../Field';
import FieldContainer from '../Field/Container';
import Fields from './index';
import Group from './Group';
import Tabs from '../Tabs';
import Wizard from '../Wizard';
import { isEmpty } from '../../../utils/conditions';
import useFieldValue from '../../../hooks/useFieldValue';
import { FieldContext } from '../../../context/FieldsContext';

export default function FieldsItem( props ) {
	const {
		field,
		updateField,
		wrap,
	} = props;

	const [ fieldValue, publishFieldValue ] = useFieldValue( field.name );

	const callbacks = useRef( null );

	if ( ! callbacks.current || callbacks.current.updateField !== updateField || callbacks.current.field !== field ) {
		callbacks.current = {
			field: field,
			updateField: updateField,
		};
		callbacks.current.updateNested = ( value ) => {
			publishFieldValue( value );
			updateField( value, field.name, field )
		};
		callbacks.current.update = ( value, name, child ) => {
			publishFieldValue( value );
			updateField( value, name ?? field.name, child ?? field )
		};
	}

	let subComponents = null;
	switch ( true ) {
		case 'object' === typeof field.tabs:
			subComponents = <Tabs { ...field } onChange={ updateField } />
			break;
		case 'object' === typeof field.wizard || 'object' === typeof field.pages:
			subComponents = <Wizard { ...field } onChange={ updateField } />
			break;
		case 'object' === typeof field.fields:
			subComponents = <Group fields={ field.fields } updateField={ updateField } inline={ field.inline } />
			break;
		case 'object' === typeof field.nested:
			subComponents = <Fields fields={ field.nested } value={ fieldValue } onChange={ callbacks.current.updateNested } />
			break;
	}

	let fieldComponent = null;
	if ( field.type && 0 > [ 'tabs', 'wizard', 'group' ].indexOf( field.type ) ) {
		fieldComponent = <Field wrap={ wrap } { ...field } value={ fieldValue ?? field.default } onChange={ callbacks.current.update } />
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
					{ ( ! isSwitch || ! isEmpty( fieldValue ) ) &&
					    <Card.Body className="border p-3">
						    { subComponents }
					    </Card.Body>
					}
				</Card>
		} else {
			items = field.label ? <FieldContainer { ...field }>{ subComponents }</FieldContainer> : subComponents;
		}

	} else if ( fieldComponent ) {
		items = fieldComponent;
	}

	return <FieldContext.Provider value={ field }>{ items }</FieldContext.Provider>;
}
