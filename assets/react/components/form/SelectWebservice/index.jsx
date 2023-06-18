import React from 'react';
import Select from "../Select";

export default function WebserviceSelector( props ) {

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
