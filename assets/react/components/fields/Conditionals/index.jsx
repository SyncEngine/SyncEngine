import React from 'react';
import Columns from "../../fields/Columns";
import { getOperators } from "../../../utils/conditionals";

export default function Conditionals( props ) {

	const {
		conditionalTypes = {},
		value,
		onChange,
	} = props;

	return (
		<Columns
			taggable={ props.taggable }
			value={ value }
			onChange={ onChange }
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
