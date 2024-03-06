import React, { useCallback, useContext, useRef } from 'react';
import { Overlay, Popover } from 'react-bootstrap';

import useToggle from '../../../hooks/useToggle';
import useRootClose from '../../../hooks/useRootClose';
import { ParentContext } from '../../../context/ParentContext';

export default function OverlayToggle( props ) {
	const {
		trigger = 'onClick',
		overlay,
		children,
		onShow,
		onHide,
		placement,
		raw,
		prewrap,
	} = props;

	const [ show, toggleShow ] = useToggle( false, onShow, onHide );
	const target = useRef( null );
	const rootClose = useRootClose( toggleShow );
	const context = useContext( ParentContext );

	const getContent = useCallback( ( content, raw, prewrap ) => {
		if ( raw ) {
			if ( React.isValidElement( content ) ) {
				return content;
			}
			content = <span dangerouslySetInnerHTML={ { __html: content } } />
		}
		if ( prewrap ) {
			content = <span style={ { whiteSpace: 'pre-wrap' } }>{ content }</span>
		}
		return (
			<Popover><Popover.Body>{ content }</Popover.Body></Popover>
		);
	}, [] );

	const getTriggerProps = useCallback( ( trigger, callback ) => {
		const props = ( Array.isArray( trigger ) ? trigger : [ trigger ] ).map( prop => {
			switch ( prop ) {
				case 'click':
					prop = 'onClick';
					break;
				case 'change':
					prop = 'onChange';
					break;
				case 'hover':
				case 'onHover':
					prop = 'onMouseOver';
					break;
				case 'focus':
					prop = 'onFocus';
					break;
			}
			return [ prop, callback ];
		} );

		return Object.fromEntries( props )
	}, [] );

	return (
		<>
			{ React.cloneElement( children, { ...getTriggerProps( trigger, toggleShow ), ref: target } ) }
			<Overlay
				ref={ rootClose }
				show={ show }
				target={ target.current }
				container={ context.container ?? target.current } // Required for input focus.
				placement={ placement }
				//rootClose={ true }
				//onHide={ toggleShow }
			>
				{ getContent( overlay, raw, prewrap ) }
			</Overlay>
		</>
	);
}
