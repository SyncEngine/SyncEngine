import React, { forwardRef } from 'react';
import useGlobal from '../../hooks/useGlobal';
import { isString } from '../../utils/conditions';
import { createSvg } from '../svg';

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

	const iconRef = isString( icon ) ? icon : null;

	if ( icon ) {
		if ( iconRef && app.icons.hasOwnProperty( iconRef ) ) {
			icon = app.icons[ iconRef ];
		}

		if ( icon instanceof Element ) {
			override.icon = null;
			override.dangerouslySetInnerHTML = { __html: icon.outerHTML };
		} else if ( icon.startsWith( '<svg' ) ) {
			icon = createSvg( icon, {
				'width': '1em',
				'height': '1em',
				'fill': 'currentColor',
			} );
			if ( iconRef ) {
				app.icons[ iconRef ] = icon;
			}
			override.icon = null;
			override.dangerouslySetInnerHTML = { __html: icon.outerHTML };
		} else if ( icon.startsWith( prefix + ' ' ) ) {
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
