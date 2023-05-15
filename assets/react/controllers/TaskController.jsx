import React from 'react';
import { objectToMappable } from "../utils/format";
import FieldController from "./FieldController";

export default function TaskController( props ) {

	const {
		value: value = {},
		name,
		label,
		description,
		onChange,
	} = props;

	if ( ! props.hasOwnProperty( 'fields' ) ) {
		return (
			<p>Task has no fields</p>
		)
	}

	const update = ( input, key ) => {
		value[ key ] = input;
		onChange( value );
	}

	return (
		<>
			{ description }
			{
				objectToMappable( props.fields, 'name' ).map( ( field, index ) => {
					return ( <FieldController { ...field } key={ index } onChange={ ( value ) => { update( value, field.name ) } }></FieldController> )
				} )
			}
		</>
	);
}
