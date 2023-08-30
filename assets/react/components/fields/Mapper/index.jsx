import React from 'react';
import Columns from '../Columns';

export default function Mapper( props ) {
	let {
		sourceKeys = {},
		targetKeys = {},
		value,
		onChange,
	} = props;

	return (
		<Columns
			value={ value }
			onChange={ onChange }
			columns={ {
				source: {
					label: 'From',
					choices: sourceKeys,
				},
				target: {
					label: 'To',
					choices: targetKeys,
				},
			} }
		/>
	);
}
