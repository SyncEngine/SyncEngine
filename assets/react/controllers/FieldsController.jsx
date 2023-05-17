import React, { useState } from 'react';
import Stack from 'react-bootstrap/Stack';
import { objectToMappable } from "../utils/format";
import FieldController from "./FieldController";
import Conditional from "../components/Conditional";
import { isEmpty } from "../utils/conditionals";

export default function FieldsController( props ) {

	const {
		onChange,
	} = props;

	const [ value, setValue ] = useState( props.value ?? {} );

	if ( ! props.hasOwnProperty( 'fields' ) ) {
		return (
			<p>No fields found.</p>
		)
	}

	const update = ( input, key ) => {
		let newValue = {...value};
		if ( ! isEmpty( input ) ) {
			newValue[ key ] = input;
		} else {
			// @todo Allow empty?
			delete newValue[ key ];
		}
		setValue( newValue );
		onChange( newValue );
	}

	return (
		<Stack gap={ 2 }>
			{
				objectToMappable( props.fields, 'name' ).map( ( field, index ) => {
					const fieldValue = value[ field.name ] ?? '';
					return (
						<Conditional key={ index } { ...field } data={ value } value={ fieldValue } >
							<FieldController { ...field } value={ fieldValue } onChange={ ( value ) => { update( value, field.name ) } }></FieldController>
						</Conditional>
					)
				} )
			}
		</Stack>
	);
}
