import React, { forwardRef } from 'react';

export default forwardRef( function Icon( props, ref ) {

	const override = {
		icon: null,
		prefix: null,
		variant: null,
	}

	const {
		icon,
		prefix = 'bi', // Bootstrap Icons.
		variant,
	} = props;

	if ( icon ) {
		override.className = props.className || '';
		override.className += ' ' + prefix;

		if ( ! icon.startsWith( prefix + '-' ) ) {
			override.className += ' ' + prefix + '-' + icon;
		} else {
			override.className += ' ' + icon;
		}

		if ( variant ) {
			override.className += ' link-' + variant;
		}
	}

	return (
		<span { ...props } { ...override } ref={ ref } />
	);
} );
