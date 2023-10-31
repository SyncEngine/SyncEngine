import React, { useCallback, useRef, useState } from 'react';

import Group from "./Group";

import { isEmpty } from "../../../utils/conditionals";
import { createRefId } from '../../../utils/globals';
import { FieldsContext } from '../../../context/FieldsContext';

export default function Fields( props ) {

	const {
		value,
		onChange,
	} = props;

	const ref = useRef( createRefId() );

	const getDefault = useCallback( ( field ) => {
		if ( -1 < [ 'checkbox', 'switch'].indexOf( field.type ) ) {
			// Keep checkbox value if default is set.
			return false;
		}
		return field.default ?? null;
	}, [] );

	const parseValue = useCallback( ( values, fields ) => {
		if ( ! fields || ( 'object' !== typeof value ) ) {
			return {};
		}
		for ( const key in fields ) {
			const name = fields[ key ].name ?? key;
			if ( isEmpty( values[ name ] ) && fields[ key ].hasOwnProperty( 'default' ) ) {
				values[ name ] = getDefault( fields[ key ] );
			}
		}
		return values;
	}, [] );

	const [ values, setValues ] = useState( parseValue( value, props.fields ) );

	const updateField = useCallback( ( input, key, field ) => {
		let newValues = { ...values };
		if ( ! isEmpty( input ) ) {
			newValues[ key ] = input;
		} else {
			if ( field && field.hasOwnProperty( 'default' ) ) {
				newValues[ key ] = getDefault( field );
			} else {
				// @todo Allow empty?
				delete newValues[ key ];
			}
		}
		setValues( newValues );
		onChange( newValues );
	}, [ value, values, onChange ] );

	return (
		<FieldsContext.Provider value={ ref.current }>
			<Group { ...props } values={ values } updateField={ updateField }></Group>
		</FieldsContext.Provider>
	);
}
