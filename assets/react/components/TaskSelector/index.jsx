import React from 'react';
import Form from 'react-bootstrap/Form';
import { objectToMappable } from "../../utils/format";

export default function TaskSelector( props ) {

	const {
		options,
		onChange,
	} = props;

	return (
		<Form.Select onChange={ ( event ) => { onChange( event.target.value ) } } value="">
			<option>-- Add Task --</option>
			{
				objectToMappable( options ).map( ( option, index ) => {
					let {
						key,
						label,
						name,
						type,
						value
					} = option;

					value = value ?? type ?? name;
					label = label ?? name ?? key ?? value;

					return ( <option key={ index } value={ value }>{ label }</option> );
				} )
			}
		</Form.Select>
	);
}
