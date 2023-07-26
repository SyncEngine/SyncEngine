import React, { useState } from 'react';
import { Button, Modal } from "react-bootstrap";

export default function DeleteModal( props ) {
	const [ open, setOpen ] = useState( false );

	const {
		text = 'Are you sure?',
		confirm = 'Delete',
		cancel = 'Cancel',
		callback,
	} = props;

	const handleClose = ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(false);
	}
	const handleShow = ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(true);
	}
	const handleConfirm = ( e ) => {
		callback();
		handleClose( e );
	}

	return (
		<>
			<span className="bi bi-trash-fill icon-link border-0 link-danger" onClick={ handleShow } />
			<div
				className="d-none"
				onKeyDown={e => e.stopPropagation()}
				onClick={e => e.stopPropagation()}
				onFocus={e => e.stopPropagation()}
				onMouseOver={e => e.stopPropagation()}
			>
				<Modal show={ open } onHide={ handleClose } centered scrollable>
					<Modal.Body>{ text }</Modal.Body>
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
