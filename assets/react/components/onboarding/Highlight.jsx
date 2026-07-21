import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { mergeClassNames } from '../../utils/props';

export default function Highlight( props ) {
	const { target } = props;
	const [ rect, setRect ] = useState( { top: 0, left: 0, width: 0, height: 0 } );

	useEffect( () => {
		if ( ! target ) return;

		const update = () => {
			const r = target.getBoundingClientRect();
			setRect( { top: r.top, left: r.left, width: r.width, height: r.height } );
		};

		update();
		const observer = new MutationObserver( update );
		observer.observe( document.body, { childList: true, subtree: true } );
		window.addEventListener( 'scroll', update, { passive: true } );
		window.addEventListener( 'resize', update, { passive: true } );

		return () => {
			observer.disconnect();
			window.removeEventListener( 'scroll', update );
			window.removeEventListener( 'resize', update );
		};
	}, [ target ] );

	return (
		<div
			className={ mergeClassNames( 'onboarding-highlight', props.className ) }
			style={ {
				top: rect.top,
				left: rect.left,
				width: rect.width,
				height: rect.height,
			} }
		/>
	);
}

Highlight.propTypes = {
	target: PropTypes.object,
	className: PropTypes.string,
};
