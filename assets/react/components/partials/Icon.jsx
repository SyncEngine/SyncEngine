import React, { forwardRef } from 'react';
import useGlobal from '../../hooks/useGlobal';
import { isString } from '../../utils/conditions';

/**
 * Parses the HTML into a DOM node which will also remove scripts and other
 * @param html
 * @return { SVGElement }
 */
function createSvg( html ) {
	const element = document.createElement('span');
	element.innerHTML = html;

	const icon = element.firstChild instanceof SVGElement ? element.firstChild : document.createElement( 'svg' );

	icon.setAttribute( 'width', '1em' );
	icon.setAttribute( 'height', '1em' );
	icon.setAttribute( 'fill', 'currentColor' );

	return icon;
}

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
			override.icon = null
			override.dangerouslySetInnerHTML = { __html: icon.outerHTML };
		} else if ( icon.startsWith( '<svg' ) ) {
			icon = createSvg( icon );
			if ( iconRef ) {
				app.icons[ iconRef ] = icon;
			}
			override.icon = null
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
