import React, { forwardRef } from 'react';
import { bool, string } from 'prop-types';
import useGlobal from '../../hooks/useGlobal';
import { isString } from '../../utils/conditions';
import { createSvg } from '../svg';

function Icon( props, ref ) {
	const app = useGlobal();

	const override = {
		className: 'icon',
		icon: null,
		prefix: null,
		variant: null,
	};

	let {
		icon,
		prefix = 'bi', // Bootstrap Icons.
		variant,
	} = props;

	if ( props.btn ) {
		override.className += ' icon-btn';
		override.btn = undefined;
	}

	if ( props.className ) {
		override.className += ' ' + props.className;
	}

	let iconRef = isString( icon ) ? icon : null;

	function loadIcon( iconRef ) {
		if ( app.icons.hasOwnProperty( iconRef ) ) {
			icon = app.icons[ iconRef ];
			if ( isString( icon ) && icon.startsWith( 'icon:' ) ) {
				icon = loadIcon( icon.substring( 5 ) );
			}
		}
		return icon;
	}

	if ( icon ) {

		if ( iconRef && app.icons.hasOwnProperty( iconRef ) ) {
			icon = loadIcon( iconRef );
		}

		if ( icon instanceof Element ) {
			override.dangerouslySetInnerHTML = { __html: icon.outerHTML };

		} else if ( icon.startsWith( '<svg' ) ) {
			// Create SVG node.
			icon = createSvg( icon, {
				'width': '1em',
				'height': '1em',
				'fill': 'currentColor',
			} );
			if ( iconRef ) {
				app.icons[ iconRef ] = icon;
			}
			override.dangerouslySetInnerHTML = { __html: icon.outerHTML };

		} else if ( icon.startsWith( 'data:image/svg+xml;base64,' ) ) {
			// Parse base64 and create SVG node.
			icon = atob( icon.substr( 26 ) );
			icon = createSvg( icon, {
				'width': '1em',
				'height': '1em',
				'fill': 'currentColor',
			} );
			if ( iconRef ) {
				app.icons[ iconRef ] = icon;
			}
			override.dangerouslySetInnerHTML = { __html: icon.outerHTML };

		// @todo, remove icon-font fallbacks?
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
};

export default forwardRef( Icon );

Icon.propTypes = {
	variant: string,
	icon: string,
	btn: bool,
}
