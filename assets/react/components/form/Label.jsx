import React, { forwardRef } from 'react';
import Icon from '../partials/Icon';
import { isObject } from '../../utils/conditions';

export default forwardRef( function Label( {
	children,
	as = React.Fragment,
	label = children,
	icon
}, ref ) {
	if ( ! label ) {
		return;
	}
	if ( React.isValidElement( label ) || ( ! as && 'string' === typeof label ) ) {
		return label;
	}

	if ( 'object' !== typeof label ) {
		label = {
			text: label,
		}
	}

	if ( ! icon ) {
		icon = label.icon && (
			isObject( label.icon ) ? label.icon : { icon: label.icon }
		);
	}

	const Component = as;

	return (
		<Component ref={ ref }>
			{ icon && <Icon className="me-1" { ...icon } /> }
			{ label.text ?? label.label }
		</Component>
	)
} )
