import React, { useState } from 'react';
import Params from "../../fields/Params";
import { getOperators } from "../../../utils/conditionals";

export default function Conditions( props ) {

	const {
		conditionTypes = {},
		value = [],
		onChange,
	} = props;

	const [ conditions, setConditions ] = useState( value );

	const updateConditions = ( newConditions ) => {
		setConditions( newConditions );
		onChange( conditions );
	}

	return (
		<Params
			value={ value }
			onChange={ updateConditions }
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
