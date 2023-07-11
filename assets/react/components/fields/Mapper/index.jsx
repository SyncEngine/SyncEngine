import React, { useState } from 'react';
import ParamsColumns from '../Params/Columns';

export default function Mapper( props ) {
	let {
		sourceKeys = [],
		targetKeys = [],
		value,
		onChange,
	} = props;

	return (
		<ParamsColumns
			value={ value }
			onChange={ onChange }
			columns={ {
				source: {
					label: 'From',
					predefined: sourceKeys,
				},
				target: {
					label: 'To',
					predefined: targetKeys,
				},
			} }
		/>
	);
}
