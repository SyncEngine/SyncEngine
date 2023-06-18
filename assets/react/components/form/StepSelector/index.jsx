import React from 'react';
import Select from "../Select";

export default function StepSelector( props ) {

	return (
		<Select
			choices={ props.options }
			label="Select Step"
			selectLabel="-- Select Step --"
			group="module"
			value=""
			{...props}
		></Select>
	)
}
