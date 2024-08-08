import React from 'react';
import { sprintf } from '../../../utils/globals';
import BadgeControl from './Badge';
import { isObject } from '../../../utils/conditions';
import { HStack } from '../../partials/Stack';

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
				<i className="bi bi-link-45deg"></i>
			</a>
			<small>{ sprintf( text ?? href, item ) }</small>
			{ badge &&
			  <BadgeControl { ...( isObject( badge ) ? badge : { label: badge } ) } item={ item } type={ type } />
			}
		</HStack>
	)
}
