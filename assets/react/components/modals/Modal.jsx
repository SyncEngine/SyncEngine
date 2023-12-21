import React, { useCallback, useRef } from 'react';
import { Modal } from 'react-bootstrap';
import { ContainerContext } from '../../context/ContainerContext';

const ModalControl = ( props ) => {
	const stopPropagation = useCallback( e => e.stopPropagation(), [] );
	return (
		<div
			className="d-none"
			onKeyDown={ stopPropagation }
			onClick={ stopPropagation }
			onFocus={ stopPropagation }
			onMouseOver={ stopPropagation }
		>
			<Modal
				dialogAs={ ModalDialog }
				{ ...props }
			/>
		</div>
	);
};

const ModalDialog = ( props ) => {
	const ref = useRef( null );
	const stopPropagation = useCallback( e => e.stopPropagation(), [] );
	return (
		<ContainerContext.Provider value={ ref }>
			<Modal.Dialog
				ref={ ref }
				onKeyDown={ stopPropagation }
				onClick={ stopPropagation }
				onFocus={ stopPropagation }
				onMouseOver={ stopPropagation }
				{ ...props }
			/>
		</ContainerContext.Provider>
	);
}

ModalControl.Dialog = ModalDialog
ModalControl.Header = Modal.Header
ModalControl.Title = Modal.Title
ModalControl.Body = Modal.Body
ModalControl.Footer = Modal.Footer
ModalControl.propTypes = Modal.propTypes
ModalControl.contextTypes = Modal.contextTypes

export default ModalControl;
