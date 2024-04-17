import React, { useCallback, useContext, useRef, useState } from 'react';

import Group from './Group';
import { createRefId } from '../../../utils/globals';
import { FieldContext, FieldsContext } from '../../../context/FieldsContext';
import { isObject } from '../../../utils/conditions';

function parseValue( value, field ) {
	if ( field.hasOwnProperty( 'default' ) ) {
		return value ?? field.default;
	}
	return value ?? null;
}

function parseValues( values, fields ) {
	if ( ! fields || ! isObject( values ) ) {
		return {};
	}
	for ( const key in fields ) {
		if ( ! fields.hasOwnProperty( key ) ) {
			continue;
		}
		const name = fields[ key ].name ?? key;
		values[ name ] = parseValue( values[ name ], fields[ key ] );
	}
	return values;
}

export default function Fields( props ) {

	const ref = useRef( createRefId() );
	const field = useContext( FieldContext );

	const {
		name = field.name,
		value,
		onChange,
	} = props;


	const [ values, setValues ] = useState( parseValues( value ?? {}, props.fields ) );

	const updateField = useCallback( ( input, key, field ) => {
		let newValues = { ...values };

		newValues[ key ] = parseValue( input, field );

		setValues( newValues );
		onChange( newValues );
	}, [ values, onChange ] );

	return (
		<FieldsContext.Provider value={ FieldsContext.create( name, values, ref.current ) }>
			<Group { ...props } updateField={ updateField }></Group>
		</FieldsContext.Provider>
	);
}
