import React from 'react';
import Icon from '../partials/Icon';
import { isObject } from '../../utils/conditions';

export default function Label( {
	children,
	label = children
} ) {
	if ( ! label ) {
		return;
	}
	if ( React.isValidElement( label ) ) {
		return label;
	}

	if ( 'object' !== typeof label ) {
		label = {
			text: label,
		}
	}

	const icon = label.icon && (
		isObject( label.icon ) ? label.icon : { icon: label.icon }
	);

	return (
		<>
			{ icon && <Icon className="me-1" { ...icon } /> }
			{ label.text }
		</>
	)
}
