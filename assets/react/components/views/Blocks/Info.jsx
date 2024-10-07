import React, { forwardRef } from 'react';
import { any, bool, object, oneOfType, string } from 'prop-types';
import { isObject } from '../../../utils/conditions';
import { HStack, VStack } from '../../partials/Stack';
import BadgeControl from './Badge';
import ValueControl from './Value';
import Icon from '../../partials/Icon';

const Info = forwardRef( function Info( props, ref ) {
	const {
		item = {},
		type = item.type,
		badge,
		inline = false,
	} = props;

	const sub = isObject( props.sub ) ? <ValueControl { ...props } { ...props.sub } /> : item[ props.sub ?? 'description' ];
	const icon = props.icon ?? item.icon;

	const label = (
		<HStack gap={2}>
			{ '' !== props.value &&
			  <span><ValueControl default="--" { ...props } /></span>
			}
			{ badge &&
			  <BadgeControl type={ type } { ...( isObject( badge ) ? badge : { label: badge } ) } item={ item } />
			}
		</HStack>
	)

	return (
		<HStack className={ props.className } ref={ ref }>
			{ icon &&
			  <Icon icon={ icon } className="fs-5 me-2 d-flex align-items-center" />
			}
			{ inline ?
				<HStack className="flex-wrap" gap={2}>
					{ label }
					{ sub && <small>{ sub }</small> }
				</HStack>
				:
				<VStack>
					{ label }
					{ sub && <small>{ sub }</small> }
				</VStack>
			}
		</HStack>
	)
} );

Info.propTypes = {
	item: object,
	type: string,
	badge: object,
	label: oneOfType( [ string, object ] ),
	sub: oneOfType( [ object, string ] ),
	icon: oneOfType( [ object, string ] ),
	value: any,
	inline: bool
};

export default Info;
