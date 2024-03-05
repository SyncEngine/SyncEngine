import React, { useCallback, useRef, useState } from 'react';

import Group from './Group';
import { createRefId } from '../../../utils/globals';
import { FieldsContext } from '../../../context/FieldsContext';
import { isObject } from '../../../utils/data';

export default function Fields( props ) {

	const {
		value,
		onChange,
	} = props;

	const ref = useRef( createRefId() );

	const parseValue = useCallback( ( value, field ) => {
		if ( field.hasOwnProperty( 'default' ) ) {
			return value ?? field.default;
		}
		return value ?? null;
	}, [] );

	const parseValues = useCallback( ( values, fields ) => {
		if ( ! fields || ! isObject( values ) ) {
			return {};
		}
		for ( const key in fields ) {
			const name = fields[ key ].name ?? key;
			values[ name ] = parseValue( values[ name ], fields[ key ] );
		}
		return values;
	}, [] );

	const [ values, setValues ] = useState( parseValues( value, props.fields ) );

	const updateField = useCallback( ( input, key, field ) => {
		let newValues = { ...values };

		newValues[ key ] = parseValue( input, field );

		setValues( newValues );
		onChange( newValues );
	}, [ value, values, onChange ] );

	return (
		<FieldsContext.Provider value={ ref.current }>
			<Group { ...props } values={ values } updateField={ updateField }></Group>
		</FieldsContext.Provider>
	);
}
