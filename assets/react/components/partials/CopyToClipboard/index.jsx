import React, { forwardRef, useCallback } from 'react';
import useClipboard from '../../../hooks/useClipboard';
import Icon from '../Icon';
import { suppress } from '../../../utils/events';

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
			onClick( e, value );
		} else {
			suppress( e );
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
	let json = clipboard && JSON.stringify( clipboard );
	let icon = ( ( ( value && clipboard ) && ( json ? JSON.stringify( value ) === json : value === clipboard ) ) ? 'clipboard-check' : 'copy' );

	return (
		<Icon
			{ ...props }
			ref={ ref }
			icon={ icon }
			className={ className + animationClass }
			onClick={ callback }
		/>
	);
} );
