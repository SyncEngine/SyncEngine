import React, { forwardRef } from 'react';
import { array, object, oneOfType, string } from 'prop-types';
import { parseTagString } from '../../../utils/tags';
import { isBool, isEmpty, isObject, isTrue, validate } from '../../../utils/conditions';
import BadgeControl from '../../partials/Badge';
import Value from './Value';
import Label from '../../form/Label';

const Badge = forwardRef( function Badge( props, ref ) {
	let {
		item,
		icon,
		type = item && item.type,
		label,
		options
	} = props;

	let fallback = true;
	let selectedOption = {};
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
				( isBool( optionData.compare ) && optionData.compare === isTrue( value ) )
					||
				( optionData.compare === value )
			) {
				selectedOption = optionData;
				if ( optionData.type ) {
					type = optionData.type;
				}
				if ( optionData.label ) {
					label = optionData.label;
				}
				if ( optionData.icon ) {
					icon = optionData.icon;
				}
				fallback = false;
				break;
			}
		}
	}

	if ( isEmpty( icon ) && ( isEmpty( label ) || true === label ) ) {
		return;
	}

	if ( 'object' === typeof label ) {
		label = <Value { ...label } item={ item }></Value>;
	} else if ( 'string' === typeof label ) {
		label = parseTagString( label, item );
	}

	return (
		<BadgeControl pill subtle className={ props.className } bg={ type } ref={ ref } title={ selectedOption?.title || props.title }>
			<Label icon={ icon && parseTagString( icon, item ) }>{ label }</Label>
		</BadgeControl>
	)
} );

Badge.propTypes = {
	item: object,
	type: string,
	label: oneOfType( [ string, object ] ),
	icon: oneOfType( [ string, object ] ),
	options: array,
}

export default Badge;
