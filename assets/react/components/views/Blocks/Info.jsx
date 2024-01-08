import React from 'react';
import { Stack } from 'react-bootstrap';
import { sprintf } from '../../../utils/globals';

export default function Info( props ) {
	const {
		item = {},
		type = item.type,
		badge,
		prop = 'label',
		fallback = 'name',
		sub = 'description',
	} = props;

	const classes = props.className ?? 'justify-content-center';

	return (
		<Stack className={ classes }>
			<span>
				{ item[ prop ] ?? item[ fallback ] ?? '--' }
				{ ( badge ?? type ) &&
					<span className={ "badge rounded-pill ms-2 " + ( type ? "text-bg-" + type : '' ) }>{ badge ? sprintf( badge, item ) : item.type }</span>
				}
				{ item._class &&
					<span className={ "badge rounded-pill ms-2 " + ( type ? "text-bg-" + type : '' ) }>{ item._class }</span>
				}
			</span>
			{ item[ sub ] &&
				<small>{ item[ sub ] }</small>
			}
		</Stack>
	)
}
