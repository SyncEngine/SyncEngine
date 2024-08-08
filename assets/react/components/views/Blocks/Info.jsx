import React, { forwardRef } from 'react';
import { Stack } from 'react-bootstrap';
import { parseTagString } from '../../../utils/tags';
import { isEmpty, isObject } from '../../../utils/conditions';
import Badge from '../../partials/Badge';
import useDateFormatter from '../../../hooks/useDateFormatter';

function ValueControl( props ) {
	const dateFormatter = useDateFormatter();

	const {
		item = {},
		prop = 'label',
		fallback = 'name',
		parse = '',
		prefix = '',
		postfix = '',
	} = props;

	let value = item[ prop ] ?? item[ fallback ] ?? props.value;

	switch ( parse ) {
		case 'date':
			value = dateFormatter.format( value * 1000 );
			break;
	}

	return value && prefix + value + postfix;
}

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
		<Badge pill subtle className={ props.className } bg={ type } ref={ ref }>{ label }</Badge>
	)
} );

function Info( props ) {
	const {
		item = {},
		type = item.type,
		badge,
	} = props;

	const classes = props.className ?? 'justify-content-center';

	let value = <ValueControl { ...props } />;

	let sub = isObject( props.sub ) ? <ValueControl { ...props } { ...props.sub } /> : item[ props.sub ?? 'description' ];

	return (
		<Stack className={ classes }>
			<span>
				{ value ?? '--' }
				{ ( badge ?? type ) &&
					<BadgeControl className={ value ? 'ms-2' : '' } content={ badge } item={ item } type={ type } />
				}
			</span>
			{ sub &&
				<small>{ sub }</small>
			}
		</Stack>
	)
}

export default Info;
