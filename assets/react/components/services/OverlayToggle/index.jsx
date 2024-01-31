import React, { useCallback, useContext, useRef } from 'react';
import { Overlay, Popover } from 'react-bootstrap';

import useToggle from '../../../hooks/useToggle';
import useRootClose from '../../../hooks/useRootClose';
import { ParentContext } from '../../../context/ParentContext';

export default function OverlayToggle( props ) {
	const {
		trigger,
		children,
		onShow,
		onHide,
		placement,
		raw,
	} = props;

	const [ show, toggleShow ] = useToggle( false, onShow, onHide );
	const target = useRef( null );
	const rootClose = useRootClose( toggleShow );
	const context = useContext( ParentContext );

	const getContent = useCallback( ( content ) => {
		if ( React.isValidElement( content ) ) {
			return content;
		}
		if ( raw ) {
			content = <span dangerouslySetInnerHTML={ { __html: content } } />
		}
		return (
			<Popover><Popover.Body>{ content }</Popover.Body></Popover>
		);
	} );

	return (
		<>
			{ React.cloneElement( trigger, { onClick: toggleShow, ref: target } ) }
			<Overlay
				ref={ rootClose }
				show={ show }
				target={ target.current }
				container={ context.container ?? target.current } // Required for input focus.
				placement={ placement }
				//rootClose={ true }
				//onHide={ toggleShow }
			>
				{ getContent( children ) }
			</Overlay>
		</>
	);
}
