import React, { useRef } from 'react';

import useToggle from '../../../hooks/useToggle';
import Modal from '../../modals/Modal';
import { isArray } from '../../../utils/conditions';

export default function ModalToggle( props ) {
	const {
		trigger,
		children,
		onShow,
		onHide,
		raw,
		prewrap,
		modalProps = {},
		toggleRef = {},
	} = props;

	const [ show, toggleShow, enableShow, disableShow ] = useToggle( false, onShow, onHide );
	const target = useRef( null );

	toggleRef.active = show;
	toggleRef.open = enableShow;
	toggleRef.close = disableShow;

	const getContent = useRef( null );
	if ( ! getContent.current ) {
		getContent.current = ( content, raw, prewrap ) => {
			if ( raw ) {
				if ( React.isValidElement( content ) || ( isArray( content ) && React.isValidElement( content[0] ) ) ) {
					return content;
				}
				content = <span dangerouslySetInnerHTML={ { __html: content } } />
			}
			if ( prewrap ) {
				content = <span style={ { whiteSpace: 'pre-wrap' } }>{ content }</span>
			}
			return (
				<Modal.Body expandButton={ modalProps.expandable }>
					{ content }
				</Modal.Body>
			);
		}
	}

	return (
		<>
			{ React.cloneElement( trigger, { onClick: toggleShow, ref: target } ) }
			<Modal centered scrollable { ...modalProps } show={ show } onHide={ disableShow }>
				{ getContent.current( children, raw, prewrap ) }
			</Modal>
		</>
	);
}
