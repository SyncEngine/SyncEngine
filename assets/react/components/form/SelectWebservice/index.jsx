import React from 'react';
import Select from "../Select";

export default function SelectWebservice( props ) {

	return (
		<Select
			choices={ props.options }
			filterKey="type"
			filterLabel="All"
			label="Select Webservice"
			selectLabel="-- Select Webservice --"
			group="module"
			value=""
			{...props}
		></Select>
	)
}
