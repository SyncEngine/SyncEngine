import React from 'react';
import Select from "../Select";

export default function TaskSelector( props ) {

	return (
		<Select
			{...props}
			choices={ props.options }
			label="Add Task"
			selectLabel="-- Select Task --"
			group="module"
			value=""
		></Select>
	)
}
