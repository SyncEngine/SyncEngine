import React, { useState } from 'react';
import Params from "../Params";

export default function Mapper( props ) {
	let {
		sourceKeys = [],
		targetKeys = [],
		value,
		onChange,
	} = props;

	return (
		<Params
			value={ value }
			onChange={ onChange }
			columns={ {
				source: {
					label: 'Source',
					predefined: sourceKeys,
				},
				target: {
					label: 'Target',
					predefined: targetKeys,
				},
			} }
		/>
	);
}
