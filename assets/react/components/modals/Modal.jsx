import React, { useCallback, useRef } from 'react';
import { Modal } from 'react-bootstrap';
import { ContainerContext } from '../../context/ContainerContext';

const ModalControl = ( props ) => {
	return (
		<Modal
			dialogAs={ ModalDialog }
			{ ...props }
		/>
	);
};

const ModalDialog = ( props ) => {

	const prevent = useCallback( e => e.stopPropagation(), [] );
	const ref = useRef( null );

	return (
		<ContainerContext.Provider value={ ref }>
			<Modal.Dialog
				ref={ ref }
				onKeyDown={ prevent }
				onClick={ prevent }
				onFocus={ prevent }
				onMouseOver={ prevent }
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
