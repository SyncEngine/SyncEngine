import React, { useCallback, useContext } from 'react';
import { Modal } from 'react-bootstrap';
import ModalContext from 'react-bootstrap/ModalContext';

const ModalControl = ( props ) => {

	const prevent = useCallback( e => e.stopPropagation(), [] );

	return (
		<Modal
			onKeyDown={ prevent }
			onClick={ prevent }
			onFocus={ prevent }
			onMouseOver={ prevent }
			{ ...props }
		/>
	);
};

ModalControl.Dialog = Modal.Dialog
ModalControl.Header = Modal.Header
ModalControl.Title = Modal.Title
ModalControl.Body = Modal.Body
ModalControl.Footer = Modal.Footer
ModalControl.propTypes = Modal.propTypes
ModalControl.contextTypes = Modal.contextTypes

export default ModalControl;
