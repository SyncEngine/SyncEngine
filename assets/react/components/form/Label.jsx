import React, { forwardRef } from 'react';
import Icon from '../partials/Icon';
import { isObject, isSet } from '../../utils/conditions';
import { node, object, oneOfType, string } from 'prop-types';

const Label = forwardRef( function Label( {
	children,
	as = React.Fragment,
	label = children,
	icon
}, ref ) {
	if ( ! label && ! icon ) {
		return;
	}

	if ( 'object' !== typeof label || React.isValidElement( label ) ) {
		label = {
			text: label,
		}
	}

	if ( ! isSet( icon ) ) {
		icon = label.icon;
	}

	const Component = as;

	const text = label.text ?? label.label;

	return (
		<Component ref={ ref }>
			{ icon && <Icon className={ text && "me-1" } { ...( isObject( icon ) ? icon : { icon: icon } ) } /> }
			{ text }
		</Component>
	)
} )

Label.propTypes = {
	as: node,
	label: oneOfType( [ string, object ] ),
	icon: oneOfType( [ string, object ] ),
}

export default Label;
