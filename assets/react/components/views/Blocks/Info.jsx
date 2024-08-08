import React, { forwardRef } from 'react';
import { parseTagString } from '../../../utils/tags';
import { isEmpty, isObject } from '../../../utils/conditions';
import Badge from '../../partials/Badge';
import useDateFormatter from '../../../hooks/useDateFormatter';
import { HStack, VStack } from '../../partials/Stack';

function ValueControl( props ) {
	const dateFormatter = useDateFormatter();

	const {
		item = {},
		prop = 'label',
		fallback = 'name',
		parse = 'item',
		prefix = '',
		postfix = '',
	} = props;

	let value = props.value ?? item[ prop ] ?? item[ fallback ];

	switch ( parse ) {
		case 'date':
			value = dateFormatter.format( value * 1000 );
			break;
		case 'tag':
			value = parseTagString( value, item );
			break;
	}

	return value ? prefix + value + postfix : props.default;
}

const BadgeControl = forwardRef( ( props, ref ) => {
	let {
		type,
		item,
		content,
	} = props;

	let label;
	if ( ! content || true === content ) {
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

	const sub = isObject( props.sub ) ? <ValueControl { ...props } { ...props.sub } /> : item[ props.sub ?? 'description' ];

	return (
		<VStack className={ classes }>
			<HStack gap={2}>
				{ '' !== props.value &&
					<span><ValueControl default="--" { ...props } /></span>
				}
				{ badge &&
					<BadgeControl content={ badge } item={ item } type={ type } />
				}
			</HStack>
			{ sub &&
				<small>{ sub }</small>
			}
		</VStack>
	)
}

export default Info;
