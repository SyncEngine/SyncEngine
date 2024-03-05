import React, { forwardRef } from 'react';
import { Button as ButtonBootstrap } from 'react-bootstrap';

export default forwardRef( function Button( props, ref ) {

	const overrides = {
		variant: props.variant ?? 'primary'
	};

	overrides.className = props.className ?? '';
	// Bootstrap compatibility.
	const outline = overrides.variant.startsWith('outline');
	const variant  = ( outline ) ? overrides.variant.substring( 0, 8 ) : overrides.variant;
	if ( props.subtle ) {
		overrides.className = overrides.className + ' btn-subtle';
		overrides.subtle = null;
	}
	if ( props.outline && ! outline ) {
		overrides.variant = 'outline-' + variant;
		overrides.outline = null;
	}
	if ( props.emphasis ) {
		overrides.className += ' btn-emphasis';
		overrides.emphasis = null;
	}

	return (
		<ButtonBootstrap { ...props } { ...overrides } ref={ ref } />
	);
} );
