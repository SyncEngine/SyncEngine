import React, { forwardRef } from 'react';

export default forwardRef( function Icon( props, ref ) {

	const override = {
		className: 'icon',
		icon: null,
		prefix: null,
		variant: null,
	}

	const {
		icon,
		prefix = 'bi', // Bootstrap Icons.
		variant,
	} = props;

	if ( props.className ) {
		override.className += ' ' + props.className;
	}

	if ( icon ) {
		if ( icon.startsWith( prefix + ' ' ) ) {
			override.className += ' ' + icon;
		} else {
			override.className += ' ' + prefix;

			if ( ! icon.startsWith( prefix + '-' ) ) {
				override.className += ' ' + prefix + '-' + icon;
			} else {
				override.className += ' ' + icon;
			}
		}

		if ( variant ) {
			override.className += ' link-' + variant;
		}
	}

	return (
		<span role="img" { ...props } { ...override } ref={ ref } />
	);
} );
