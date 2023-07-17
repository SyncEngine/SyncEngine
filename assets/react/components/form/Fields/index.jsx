import React, { useState } from 'react';

import Group from "./Group";

import { isEmpty } from "../../../utils/conditionals";

export default function Fields( props ) {

	const {
		value,
		onChange,
	} = props;

	const [ values, setValues ] = useState( ( 'object' === typeof value ) ? value : {} );

	const updateField = ( input, key, field ) => {
		let newValues = { ...values };
		if ( ! isEmpty( input ) ) {
			newValues[ key ] = input;
		} else {
			if ( field.hasOwnProperty( 'default' ) ) {
					newValues[ key ] = field.default;
			} else {
				// @todo Allow empty?
				delete newValues[ key ];
			}
		}
		setValues( newValues );
		onChange( newValues );
	}

	return (
		<Group { ...props } values={ values } updateField={ updateField }></Group>
	);
}
