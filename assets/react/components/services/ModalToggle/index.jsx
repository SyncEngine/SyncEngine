import React, { useRef } from 'react';

import useToggle from '../../../hooks/useToggle';
import Modal from '../../modals/Modal';

export default function ModalToggle( props ) {
	const {
		trigger,
		children,
		onShow,
		onHide,
		raw,
		prewrap,
	} = props;

	const [ show, toggleShow, enableShow, disableShow ] = useToggle( false, onShow, onHide );
	const target = useRef( null );

	const getContent = useRef( null );
	if ( ! getContent.current ) {
		getContent.current = ( content, raw, prewrap ) => {
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
				<Modal.Body>{ content }</Modal.Body>
			);
		}
	}

	return (
		<>
			{ React.cloneElement( trigger, { onClick: toggleShow, ref: target } ) }
			<Modal show={ show } onHide={ disableShow } centered scrollable>
				{ getContent.current( children, raw, prewrap ) }
			</Modal>
		</>
	);
}
