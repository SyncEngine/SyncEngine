import React from 'react';
import { sprintf } from '../../../utils/globals';
import BadgeControl from './Badge';
import { isObject } from '../../../utils/conditions';
import { HStack } from '../../partials/Stack';
import Icon from '../../partials/Icon';

export default function Link( props ) {
	const {
		item,
		type = item.type,
		text,
		href,
		badge,
	} = props;

	if ( ! href ) {
		return;
	}

	return (
		<HStack gap={ 2 }>
			<a className="icon-link" href={ sprintf( href, item ) } target="_blank">
				<Icon icon="link" />
			</a>
			<small>{ sprintf( text ?? href, item ) }</small>
			{ badge &&
			  <BadgeControl type={ type } { ...( isObject( badge ) ? badge : { label: badge } ) } item={ item } />
			}
		</HStack>
	)
}
