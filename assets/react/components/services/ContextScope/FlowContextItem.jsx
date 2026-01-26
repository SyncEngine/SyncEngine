import React from 'react';

export default function FlowContextItem( props ) {

	const {
		item
	} = props;

	const steps = item.config?.steps;

	return (
		<>
			<b>{ item._entity }:</b> { item.name }
		</>
	);
}
