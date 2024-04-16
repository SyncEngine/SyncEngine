import React, { useCallback, useContext, useRef, useState } from 'react';

import Group from './Group';
import { createRefId } from '../../../utils/globals';
import { FieldsContext } from '../../../context/FieldsContext';
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
		const name = fields[ key ].name ?? key;
		values[ name ] = parseValue( values[ name ], fields[ key ] );
	}
	return values;
}

export default function Fields( props ) {

	const {
		value,
		onChange,
	} = props;

	const ref = useRef( createRefId() );

	const parent = useContext( FieldsContext ) ?? null;

	const [ values, setValues ] = useState( parseValues( value, props.fields ) );

	const updateField = useCallback( ( input, key, field ) => {
		let newValues = { ...values };

		newValues[ key ] = parseValue( input, field );

		setValues( newValues );
		onChange( newValues );
	}, [ values, onChange ] );

	return (
		<FieldsContext.Provider value={ {
           parent: parent,
           root: parent.root ?? null,
           path: ( parent.path ?? '' ) + '.' + ref.current,
           id: ref.current,
           values: values,
       } }>
			<Group { ...props } updateField={ updateField }></Group>
		</FieldsContext.Provider>
	);
}
