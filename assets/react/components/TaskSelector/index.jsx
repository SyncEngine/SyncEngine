import React from 'react';
import Form from 'react-bootstrap/Form';
import { objectToMappable } from "../../utils/format";

export function TaskSelector( props ) {

	const {
		options,
	} = props;

	return (
		<>
			<h2>Add Task</h2>
			<Form.Select>
				{
					objectToMappable( options ).map( ( option, index ) => {
						let {
							key,
							label,
							name,
							type,
							value
						} = option;

						value = value ?? type;
						label = label ?? name ?? key ?? value;

						return ( <option key={ index } value={ value }>{ label }</option> );
					} )
				}
			</Form.Select>
		</>
	);
}
