import React, { useState } from 'react';
import Columns from "../../fields/Columns";
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
		<Columns
			value={ value }
			onChange={ updateConditionals }
			columns={ {
				key: {
					label: 'Field',
				},
				operator: {
					label: 'Operator',
					choices: {
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
