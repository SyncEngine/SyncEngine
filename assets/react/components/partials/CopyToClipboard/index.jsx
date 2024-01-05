import React, { forwardRef, useCallback } from 'react';
import useClipboard from '../../../hooks/useClipboard';

export default forwardRef( function CopyToClipboard( props, ref ) {
	const [ clipboard, setClipboard ] = useClipboard( 'clipboard' );

	const {
		className = 'btn',
		animation = 'zoom',
		transition = 'fast',
		onClick,
		value,
	} = props;

	const callback = useCallback( ( e ) => {
		if ( 'function' === typeof onClick ) {
			onClick( e );
		} else {
			e.preventDefault();
			e.stopPropagation();
			setClipboard( value );
		}
	}, [ value, onClick ] );

	let animationClass = ' ';
	switch ( animation ) {
		default:
			animationClass += 'scale-110-hover';
			break;
	}

	if ( transition ) {
		animationClass += ' transition-all transition-' + transition;
	}

	// @todo Improve performance, maybe drop JSON?
	let iconClass = ' bi ' + ( JSON.stringify( value ) === JSON.stringify( clipboard ) ? 'bi-clipboard-check' : 'bi-copy' );

	return (
		<span
			{ ...props }
			ref={ ref }
			className={ className + iconClass + animationClass }
			onClick={ callback }
		/>
	);
} );
