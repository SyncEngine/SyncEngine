import React from 'react';
import { Stack } from 'react-bootstrap';
import { parseTagString } from '../../../utils/tags';

export default function Info( props ) {
	const {
		item = {},
		type = item.type,
		badge,
		prop = 'label',
		fallback = 'name',
		sub = 'description',
		parse = '',
	} = props;

	const classes = props.className ?? 'justify-content-center';

	let value = item[ prop ] ?? item[ fallback ];

	switch ( parse ) {
		case 'date':
			value = new Date( value * 1000 ).toLocaleString();
			break;
	}

	return (
		<Stack className={ classes }>
			<span>
				{ value ?? '--' }
				{ ( badge ?? type ) &&
					<span className={ "badge rounded-pill ms-2 " + ( type ? "text-bg-" + type : '' ) }>{ badge ? parseTagString( badge, item ) : item.type }</span>
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
