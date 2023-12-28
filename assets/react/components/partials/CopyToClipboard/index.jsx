import React, { useEffect, useState, forwardRef, useCallback } from 'react';
import useClipboard from '../../../hooks/useClipboard';

export default forwardRef( function CopyToClipboard( props ) {
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

	let iconClass = ' bi ' + ( JSON.stringify( value ) === JSON.stringify( clipboard ) ? 'bi-clipboard-check' : 'bi-copy' );

	return (
		<span
			{ ...props }
			className={ className + iconClass + animationClass }
			onClick={ callback }
		/>
	);
} );
