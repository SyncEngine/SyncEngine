import React, { useState } from 'react';

import Group from "./Group";

import { isEmpty } from "../../../utils/conditionals";

export default function Fields( props ) {

	const {
		value,
		onChange,
	} = props;

	const [ values, setValues ] = useState( value );

	const updateField = ( input, key ) => {
		let newValues = { ...values };
		if ( ! isEmpty( input ) ) {
			newValues[ key ] = input;
		} else {
			// @todo Allow empty?
			delete newValues[ key ];
		}
		setValues( newValues );
		onChange( newValues );
	}

	return (
		<Group { ...props } values={ values } updateField={ updateField }></Group>
	);
}
