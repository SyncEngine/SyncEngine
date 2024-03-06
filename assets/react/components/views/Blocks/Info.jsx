import React, { forwardRef } from 'react';
import { Stack } from 'react-bootstrap';
import { parseTagString } from '../../../utils/tags';
import { isEmpty } from '../../../utils/conditions';
import Badge from '../../partials/Badge';
import useDateFormatter from '../../../hooks/useDateFormatter';

const BadgeControl = forwardRef( ( props, ref ) => {
	let {
		type,
		item,
		content,
	} = props;

	let label;
	if ( ! content ) {
		label = item.type;

		if ( ! label ) {
			return;
		}
	} else if ( 'string' === typeof content ) {
		label = parseTagString( content, item );
	} else {
		let fallback = true;
		if ( ! isEmpty( content.options ) ) {
			for ( const option in content.options ) {
				if ( ! content.options[ option ] ) {
					continue;
				}
				const optionData = content.options[ option ];
				if ( optionData.compare === parseTagString( optionData.value, item ) ) {
					if ( optionData.type ) {
						type = optionData.type;
					}
					if ( optionData.label ) {
						label = parseTagString( optionData.label, item );
					}
					fallback = false;
					break;
				}
			}
		}

		if ( fallback ) {
			if ( content.type ) {
				type = content.type;
			}
			if ( content.label ) {
				label = parseTagString( content.label, item );
			}
		}
	}

	return (
		<Badge pill subtle className='ms-2' bg={ type } ref={ ref }>{ label }</Badge>
	)
} );

function Info( props ) {
	const dateFormatter = useDateFormatter();

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
			value = dateFormatter.format( value * 1000 );
			break;
	}

	return (
		<Stack className={ classes }>
			<span>
				{ value ?? '--' }
				{ ( badge ?? type ) &&
					<BadgeControl content={ badge } item={ item } type={ type } />
				}
			</span>
			{ item[ sub ] &&
				<small>{ item[ sub ] }</small>
			}
		</Stack>
	)
}

export default Info;
