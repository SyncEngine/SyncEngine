import React from 'react';
import { objectToMappable } from "../utils/format";
import FieldController from "./FieldController";

export default function TaskController( props ) {

	const {
		name,
		label,
		description,
	} = props;

	if ( ! props.hasOwnProperty( 'fields' ) ) {
		return (
			<p>Task has no fields</p>
		)
	}

	return (
		<>
			{ description }
			{
				objectToMappable( props.fields, 'name' ).map( ( field, index ) => {
					return ( <FieldController key={ index } { ...field }></FieldController> )
				} )
			}
		</>
	);
}
