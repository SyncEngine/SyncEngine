import React, { useCallback, useContext, useRef, useState } from 'react';

import Group from './Group';
import { createRefId } from '../../../utils/globals';
import { FieldContext, FieldsContext } from '../../../context/FieldsContext';
import { isObject } from '../../../utils/conditions';
import { parseValue } from '../../../hooks/useFieldValue';

export function parseValues( values, fields ) {
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
		fieldsContext,
	} = props;

	const [ values, setValues ] = useState( parseValues( value ?? {}, props.fields ) );

	const updateField = useCallback( ( input, key, field ) => {
		values[ key ] = parseValue( input, field );

		setValues( values );
		onChange( values );
	}, [ onChange ] );

	return (
		<FieldsContext.Provider value={ fieldsContext ?? FieldsContext.create( name, values, ref.current ) }>
			<Group { ...props } updateField={ updateField } value={ undefined }></Group>
		</FieldsContext.Provider>
	);
}
