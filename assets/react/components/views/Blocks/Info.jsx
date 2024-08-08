import React from 'react';
import { isObject } from '../../../utils/conditions';
import { HStack, VStack } from '../../partials/Stack';
import BadgeControl from './Badge';
import ValueControl from './Value';

export default function Info( props ) {
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
					<BadgeControl type={ type } { ...( isObject( badge ) ? badge : { label: badge } ) } item={ item } />
				}
			</HStack>
			{ sub &&
				<small>{ sub }</small>
			}
		</VStack>
	)
}
