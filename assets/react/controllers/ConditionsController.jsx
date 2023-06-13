import React, { useEffect, useState } from 'react';
import { Stack, } from 'react-bootstrap';
import Params from "../components/Params";
import { getOperators } from "../utils/conditionals";

export default function ConditionsController( props ) {

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
		<Stack gap={2}>
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
		</Stack>
	);
}
