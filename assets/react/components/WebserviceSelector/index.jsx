import React from 'react';
import Select from "../Select";

export default function WebserviceSelector( props ) {

	return (
		<Select
			label="Select Webservice"
			selectLabel="-- Select Webservice --"
			choices={ props.options }
			group="module"
			value=""
			{...props}
		></Select>
	)
}
