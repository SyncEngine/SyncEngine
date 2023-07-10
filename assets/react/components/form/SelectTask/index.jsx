import React from 'react';
import Select from "../Select";

export default function SelectTask( props ) {

	return (
		<Select
			choices={ props.options }
			filterKey="type"
			filterLabel="All"
			label="Select Task"
			selectLabel="-- Select Task --"
			group="module"
			value=""
			{...props}
		></Select>
	)
}
