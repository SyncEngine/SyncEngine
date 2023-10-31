import React, { cloneElement, useCallback, useState } from 'react';
import { Button, Modal } from "react-bootstrap";
import ModalWrapper from '../ModelWrapper';

export default function ConfirmModal( props ) {
	const [ open, setOpen ] = useState( false );

	const {
		header = '',
		text = '',
		confirm = 'Confirm',
		cancel = 'Cancel',
		trigger = 'onClick',
		callback,
	} = props;

	const handleClose = useCallback( ( e ) => {
		if ( e && 'function' === typeof e.preventDefault ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(false);
	}, [] );

	const handleOpen = useCallback( ( e ) => {
		if ( e && 'function' === typeof e.preventDefault ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(true);
	}, [] );

	const handleConfirm = useCallback( ( e ) => {
		callback();
		handleClose( e );
	}, [ callback ] );

	const getTriggerProps = () => {
		const props = Array.isArray( trigger ) ? trigger : [ trigger ].map( prop => {
			switch ( prop ) {
				case 'click':
					prop = 'onClick';
					break;
				case 'change':
					prop = 'onChange';
					break;
				case 'hover':
					prop = 'onHover';
					break;
				case 'focus':
					prop = 'onFocus';
					break;
			}
			return [ prop, handleOpen ];
		} );

		return Object.fromEntries( props )
	}

	return (
		<>
			{ typeof props.children === 'function' ? props.children( getTriggerProps() ) : cloneElement( props.children, getTriggerProps() ) }
			<ModalWrapper>
				<Modal show={ open } onHide={ handleClose } centered scrollable>
					{ header &&
					  <Modal.Header closeButton>{ header }</Modal.Header>
					}
					{ text &&
						<Modal.Body>{ text }</Modal.Body>
					}
					<Modal.Footer>
						<Button variant="secondary" onClick={ handleClose } autoFocus>
							{ cancel }
						</Button>
						<Button variant="danger" onClick={ handleConfirm }>
							{ confirm }
						</Button>
					</Modal.Footer>
				</Modal>
			</ModalWrapper>
		</>
	);
}
