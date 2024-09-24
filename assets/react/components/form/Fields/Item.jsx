import React, { useRef } from 'react';
import { Card } from 'react-bootstrap';

import Field from '../Field';
import FieldContainer from '../Field/Container';
import Fields from './index';
import Group from './Group';
import Tabs from '../Tabs';
import Wizard from '../Wizard';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import useFieldValue from '../../../hooks/useFieldValue';
import { FieldContext } from '../../../context/FieldsContext';

export default function FieldsItem( props ) {
	const editable = isFieldEditable( props );
	const {
		field,
		updateField,
		wrap,
	} = props;

	const [ fieldValue ] = useFieldValue( field.name );

	const callbacks = useRef( null );

	if (
		! callbacks.current
		|| callbacks.current.updateField !== updateField
		|| callbacks.current.field !== field
		|| callbacks.current.editable !== editable
	) {
		callbacks.current = {
			editable: editable,
			field: field,
			updateField: updateField,
		};
		callbacks.current.updateNested = ( value ) => {
			if ( editable ) {
				updateField( value, field.name, field );
			}
		};
		callbacks.current.update = ( value, name, child ) => {
			if ( editable ) {
				updateField( value, name ?? field.name, child ?? field );
			}
		};
	}

	let subComponents = null;
	switch ( true ) {
		case 'object' === typeof field.tabs:
			subComponents = <Tabs { ...field } editable={ editable } onChange={ updateField } />
			break;
		case 'object' === typeof field.wizard || 'object' === typeof field.pages:
			subComponents = <Wizard { ...field } editable={ editable } onChange={ updateField } />
			break;
		case 'object' === typeof field.fields:
			subComponents = <Group fields={ field.fields } editable={ editable } updateField={ updateField } inline={ field.inline } />
			break;
		case 'object' === typeof field.nested:
			subComponents = <Fields fields={ field.nested } editable={ editable } value={ fieldValue } onChange={ callbacks.current.updateNested } />
			break;
	}

	let fieldComponent = null;
	if ( field.type && 0 > [ 'tabs', 'wizard', 'group' ].indexOf( field.type ) ) {
		fieldComponent = <Field wrap={ wrap } { ...field } editable={ editable } value={ fieldValue ?? field.default } onChange={ callbacks.current.update } />
	}

	let items = null;
	if ( subComponents ) {

		if ( fieldComponent ) {
			const isSwitch = -1 < [ 'checkbox', 'switch', 'toggle' ].indexOf( field.type );
			items =
				<Card className="bg-body border-0">
					<Card.Header className={ "bg-body " + ( isSwitch ? 'p-input-container border' : ' p-0 border-0' ) }>
						{ fieldComponent }
					</Card.Header>
					{ ( ! isSwitch || ! isEmpty( fieldValue ) ) &&
					    <Card.Body className="border p-input-container">
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

	return <FieldContext.Provider value={ FieldContext.create( field ) }>{ items }</FieldContext.Provider>;
}
