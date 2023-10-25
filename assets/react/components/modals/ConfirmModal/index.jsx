import React, { cloneElement, useCallback, useState } from 'react';
import { Button, Modal } from "react-bootstrap";

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
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(false);
	}, [] );

	const handleShow = useCallback( ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(true);
	}, [] );

	const handleConfirm = useCallback( ( e ) => {
		callback();
		handleClose( e );
	}, [ callback ] );

	const triggerProps = {
		[ trigger ]: handleShow,
	}

	return (
		<>
			{ typeof props.children === 'function' ? props.children( triggerProps ) : cloneElement( props.children, triggerProps ) }
			<div
				className="d-none"
				onKeyDown={e => e.stopPropagation()}
				onClick={e => e.stopPropagation()}
				onFocus={e => e.stopPropagation()}
				onMouseOver={e => e.stopPropagation()}
			>
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
			</div>
		</>
	);
}
