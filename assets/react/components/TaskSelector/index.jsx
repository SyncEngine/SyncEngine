import React from 'react';
import Select from "../Select";

export default function TaskSelector( props ) {

	return (
		<Select
			choices={ props.options }
			label="Select Task"
			selectLabel="-- Select Task --"
			group="module"
			value=""
			{...props}
		></Select>
	)
}
