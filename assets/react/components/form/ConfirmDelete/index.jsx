import React, { useState } from 'react';
import { Button, Modal } from "react-bootstrap";
import { BsTrashFill } from "react-icons/bs";

export default function ConfirmDelete( props ) {
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
			<BsTrashFill className="mx-2 icon-link border-0 link-danger" onClick={ handleShow } />
			<div onClick={ handleClose }>
				<Modal show={ open }>
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
