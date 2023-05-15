import React from 'react';
import Form from 'react-bootstrap/Form';
import { objectToMappable } from "../../utils/format";

export default function TaskSelector( props ) {

	const {
		options,
	} = props;

	return (
		<>
			<h3>Add Task</h3>
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
