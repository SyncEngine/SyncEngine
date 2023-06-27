import React, { useState } from 'react';
import Params from "../../fields/Params";
import { getOperators } from "../../../utils/conditionals";

export default function Conditionals( props ) {

	const {
		conditionalTypes = {},
		value = [],
		onChange,
	} = props;

	const [ conditionals, setConditionals ] = useState( value );

	const updateConditionals = ( newConditionals ) => {
		setConditionals( newConditionals );
		onChange( conditionals );
	}

	return (
		<Params
			value={ value }
			onChange={ updateConditionals }
			columns={ {
				key: {
					label: 'Field',
				},
				operator: {
					label: 'Operator',
					predefined: {
						'': '-- Select --',
						...getOperators()
					},
				},
				compare: {
					label: 'Compare',
					mutliple: true,
				}
			} }
		/>
	);
}
