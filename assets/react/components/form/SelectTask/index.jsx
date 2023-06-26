import React from 'react';
import Select from "../Select";

export default function SelectTask( props ) {

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
