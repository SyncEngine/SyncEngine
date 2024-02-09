import React, { forwardRef } from 'react';
import { Button as ButtonBootstrap } from 'react-bootstrap';

export default forwardRef( function Button( props, ref ) {

	const overrides = {};
	if ( props.variant ) {
		overrides.variant = props.variant;
		// Bootstrap compatibility.
		const outline = overrides.variant.startsWith('outline');
		if ( props.subtle ) {
			const variant  = ( outline ) ? overrides.variant.substring( 0, 8 ) : overrides.variant;
			overrides.className = props.className + ' btn-' + variant + '-subtle';
			overrides.subtle = null;
		}
		if ( props.outline && ! outline ) {
			overrides.variant = 'outline-' + overrides.variant;
			overrides.outline = null;
		}
	}

	return (
		<ButtonBootstrap { ...props } { ...overrides } ref={ ref } />
	);
} );
