import React, { forwardRef } from 'react';
import { Badge as BadgeBootstrap } from 'react-bootstrap';

export default forwardRef( function Badge( props, ref ) {

	const overrides = {
		bg: props.bg ?? 'primary'
	};

	if ( props.subtle ) {
		if ( ! props.text ) {
			overrides.text = overrides.bg + '-emphasis';
		}
		overrides.bg = overrides.bg + '-subtle';
		overrides.subtle = null;
	}
	if ( props.outline ) {
		if ( ! props.text && ! overrides.text ) {
			overrides.text = overrides.bg;
		}
		if ( ! props.subtle ) {
			overrides.bg = '';
		}
		overrides.className = props.className + ' border border-' + props.bg;
		if ( 1 < props.outline ) {
			overrides.className += ' border-' + props.outline;
		}
		overrides.outline = null;
	}

	return (
		<BadgeBootstrap { ...props } { ...overrides } ref={ ref } />
	);
} );
