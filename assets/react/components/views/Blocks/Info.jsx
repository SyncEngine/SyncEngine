import React from 'react';
import { Stack } from 'react-bootstrap';
import { sprintf } from '../../../utils/globals';

export default function Info( props ) {
	const {
		item,
		type,
		badge,
	} = props;

	const classes = props.className ?? 'justify-content-center';

	return (
		<Stack className={ classes }>
			<span>
				{ item.label ?? item.name ?? '--' }
				{ ( badge ?? item.type ) &&
					<span className={ "badge rounded-pill ms-2 " + ( type ? "text-bg-" + type : '' ) }>{ badge ? sprintf( badge, item ) : item.type }</span>
				}
				{ item._class &&
					<span className={ "badge rounded-pill ms-2 " + ( type ? "text-bg-" + type : '' ) }>{ item._class }</span>
				}
			</span>
			{ item.description &&
				<small>{ item.description }</small>
			}
		</Stack>
	)
}
