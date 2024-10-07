import React, { forwardRef } from 'react';
import { object, string } from 'prop-types';
import { parseTagString } from '../../../utils/tags';
import { isBool, isEmpty, isObject, validate } from '../../../utils/conditions';
import BadgeControl from '../../partials/Badge';
import Value from './Value';

function Badge( props, ref ) {
	let {
		item,
		type = item && item.type,
		label,
		options
	} = props;

	let fallback = true;
	if ( ! isEmpty( options ) ) {
		for ( const option in options ) {
			if ( ! options[ option ] ) {
				continue;
			}
			const optionData = options[ option ];
			const value = parseTagString( optionData.value, item );

			if (
				( isObject( optionData.compare ) && validate( optionData.compare, value ) )
					||
				( isBool( optionData.compare ) && optionData.compare === ! isEmpty( value ) )
					||
				( optionData.compare === value )
			) {
				if ( optionData.type ) {
					type = optionData.type;
				}
				if ( optionData.label ) {
					label = optionData.label;
				}
				fallback = false;
				break;
			}
		}
	}

	if ( isEmpty( label ) || true === label ) {
		return;
	}

	if ( 'object' === typeof label ) {
		label = <Value { ...label } item={ item }></Value>;
	} else if ( 'string' === typeof label ) {
		label = parseTagString( label, item );
	}

	return (
		<BadgeControl pill subtle className={ props.className } bg={ type } ref={ ref }>{ label }</BadgeControl>
	)
}

Badge.propTypes = {
	item: object,
	type: string,
	label: string,
	options: object,
}

export default forwardRef( Badge );
