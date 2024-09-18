import React, { forwardRef } from 'react';
import useGlobal from '../../hooks/useGlobal';

export default forwardRef( function Icon( props, ref ) {
	const app = useGlobal();

	const override = {
		className: 'icon',
		icon: null,
		prefix: null,
		variant: null,
	}

	let {
		icon,
		prefix = 'bi', // Bootstrap Icons.
		variant,
	} = props;

	if ( props.className ) {
		override.className += ' ' + props.className;
	}

	if ( icon ) {
		if ( app.icons.hasOwnProperty( icon ) ) {
			icon = app.icons[ icon ];
		}

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
