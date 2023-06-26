import React from 'react';
import Select from "../Select";

export default function SelectWebservice( props ) {

	return (
		<Select
			choices={ props.options }
			label="Select Webservice"
			selectLabel="-- Select Webservice --"
			group="module"
			value=""
			{...props}
		></Select>
	)
}
