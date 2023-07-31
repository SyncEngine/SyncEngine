import React, { useCallback, useState } from 'react';

import Group from "./Group";

import { isEmpty } from "../../../utils/conditionals";

export default function Fields( props ) {

	const {
		value,
		onChange,
	} = props;

	const [ values, setValues ] = useState( ( 'object' === typeof value ) ? value : {} );

	const updateField = useCallback( ( input, key, field ) => {
		let newValues = { ...values };
		if ( ! isEmpty( input ) ) {
			newValues[ key ] = input;
		} else {
			if ( field.hasOwnProperty( 'default' ) ) {
				if ( -1 < [ 'checkbox', 'switch'].indexOf( field.type ) ) {
					// Keep checkbox value if default is set.
					newValues[ key ] = false;
				} else {
					newValues[ key ] = field.default;
				}
			} else {
				// @todo Allow empty?
				delete newValues[ key ];
			}
		}
		setValues( newValues );
		onChange( newValues );
	}, [ values, onChange ] );

	return (
		<Group { ...props } values={ values } updateField={ updateField }></Group>
	);
}
