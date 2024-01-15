import React, { useCallback, useRef } from 'react';
import { Overlay, Popover } from 'react-bootstrap';

import useToggle from '../../../hooks/useToggle';
import useRootClose from '../../../hooks/useRootClose';

export default function OverlayToggle( props ) {
	const {
		trigger,
		children,
		onShow,
		onHide,
	} = props;

	const [ show, toggleShow ] = useToggle( false, onShow, onHide );
	const target = useRef( null );
	const rootClose = useRootClose( toggleShow );

	const getContent = useCallback( ( content ) => {
		if ( React.isValidElement( content ) ) {
			return content;
		}
		return (
			<Popover>{ content }</Popover>
		);
	} );

	return (
		<>
			{ React.cloneElement( trigger, { onClick: toggleShow, ref: target } ) }
			<Overlay
				ref={ rootClose }
				show={ show }
				target={ target.current }
				container={ target.current } // Required for input focus.
				//rootClose={ true }
				//onHide={ toggleShow }
			>
				{ getContent( children ) }
			</Overlay>
		</>
	);
}
