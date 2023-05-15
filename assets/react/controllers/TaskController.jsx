import React from 'react';

export function TaskController( props ) {

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

	let fields = [];
	if ( 'function' !== typeof props.fields.map ) {
		for ( const key in props.fields ) {
			if ( ! props.fields.hasOwnProperty( key ) ) {
				continue;
			}
			if ( ! props.fields[ key ].name ) {
				props.fields[ key ].name = key;
			}
			fields.push( props.fields[ key ] );
		}
	} else {
		fields = props.fields;
	}

	return (
		<>
			{ description }
			{
				fields.map( ( field, index ) => {
					return ( <FieldController key={ index } {...field}></FieldController> )
				} )
			}
		</>
	);
}
