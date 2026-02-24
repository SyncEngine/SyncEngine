import React, { forwardRef } from 'react';
import { Button as ButtonBootstrap } from 'react-bootstrap';

export default forwardRef( function Button( props, ref ) {

	const overrides = parseButtonProps( props );

	return (
		<ButtonBootstrap { ...props } { ...overrides } ref={ ref } />
	);
} );

export function parseButtonProps( props ) {
	const overrides = {
		variant: props.variant ?? 'primary'
	};

	overrides.className = props.className ?? '';
	// Bootstrap compatibility.
	const outline = overrides.variant.startsWith('outline');
	const variant  = ( outline ) ? overrides.variant.substring( 0, 8 ) : overrides.variant;
	if ( props.subtle ) {
		overrides.className = overrides.className + ' btn-subtle';
	}
	if ( props.outline && ! outline ) {
		overrides.variant = 'outline-' + variant;
	}
	if ( props.emphasis ) {
		overrides.className += ' btn-emphasis';
	}

	overrides.subtle = undefined;
	overrides.outline = undefined;
	overrides.emphasis = undefined;

	return overrides;
}

export function getButtonClasses( props ) {
	let classes = 'btn';

	if ( props.variant ) {
		// Bootstrap compatibility.
		const outline = props.variant.startsWith('outline');
		if ( props.outline && ! outline ) {
			classes += ' btn-outline-' + props.variant;
		} else {
			classes += ' btn-' + props.variant;
		}
		if ( props.subtle ) {
			classes += ' btn-subtle';
		}
		if ( props.emphasis ) {
			classes += ' btn-emphasis';
		}
	}

	return classes;
}
