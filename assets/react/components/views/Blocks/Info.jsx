import React, { forwardRef } from 'react';
import { isObject } from '../../../utils/conditions';
import { HStack, VStack } from '../../partials/Stack';
import BadgeControl from './Badge';
import ValueControl from './Value';
import Icon from '../../partials/Icon';

export default forwardRef( function Info( props, ref ) {
	const {
		item = {},
		type = item.type,
		badge,
	} = props;

	const sub = isObject( props.sub ) ? <ValueControl { ...props } { ...props.sub } /> : item[ props.sub ?? 'description' ];
	const icon = props.icon ?? item.icon;

	return (
		<HStack className={ props.className } ref={ ref }>
			{ icon &&
			  <Icon icon={ icon } className="fs-5 me-2 d-flex align-items-center" />
			}
			<VStack>
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
		</HStack>
	)
} );
