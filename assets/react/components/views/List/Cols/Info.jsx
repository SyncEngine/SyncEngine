import React from 'react';

export default function Info( props ) {
	const {
		entity,
		item = entity,
	} = props;

	return (
		<>
			{ item.name }
			{ item.type &&
				<span className={ "badge rounded-pill" + ( item.variant ? "text-bg-" + item.variant : '' ) }>{ item.type }</span>
			}
			{ item.description &&
				<><br /><small>{ item.description }</small></>
			}
		</>
	)
}
