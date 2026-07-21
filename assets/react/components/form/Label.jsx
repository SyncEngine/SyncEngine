import React, { forwardRef } from 'react';
import Icon from '../partials/Icon';
import { isObject, isSet } from '../../utils/conditions';
import { bool, func, node, object, oneOfType, string } from 'prop-types';
import { suppress } from '../../utils/events';

const Label = forwardRef( function Label( {
	children,
	as = React.Fragment,
	label = children,
	icon,
	editable,
	onChange
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

	let text = label.text ?? label.label;

	if ( icon ) {
		icon = <Icon className={ text && "me-1" } { ...( isObject( icon ) ? icon : { icon: icon } ) } />;
	}

	if ( editable && onChange && 'string' === typeof text ) {
		text = <>
			<span
				suppressContentEditableWarning
				contentEditable
				onBlur={ ( e ) => { e.target.innerText !== text ? onChange( e.target.innerText ) : null } }
				onClick={ suppress }
				className="d-inline-block focus-ring"
				style={ { cursor: 'text', minWidth: '3em' } }
			>
				{ text }
			</span>
		</>
	}

	if ( Component === React.Fragment ) {
		return <Component>{ icon }{ text }</Component>
	}

	return <Component ref={ ref }>{ icon }{ text }</Component>
} )

Label.propTypes = {
	as: node,
	label: oneOfType( [ string, object, node ] ),
	icon: oneOfType( [ string, object ] ),
	editable: bool,
	onChange: func,
}

export default Label;
