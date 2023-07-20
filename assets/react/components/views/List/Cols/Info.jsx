import React from 'react';

export default function Info( props ) {
	const {
		item,
		type,
	} = props;

	return (
		<>
			{ item.name }
			{ item.type &&
				<span className={ "badge rounded-pill ms-2 " + ( type ? "text-bg-" + type : '' ) }>{ item.type }</span>
			}
			{ item.description &&
				<><br /><small>{ item.description }</small></>
			}
		</>
	)
}
