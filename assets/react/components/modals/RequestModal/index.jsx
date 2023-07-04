import React, { useState, cloneElement } from 'react';
import { Button, Modal, Spinner } from "react-bootstrap";

import FormStatic from "../../form/FormStatic";

import { isEmpty } from "../../../utils/conditionals";
import { parseForm } from "../../../utils/form";
import { fetchPost } from "../../../utils/fetch";
import { ElementContext } from "../../context/ElementContext";

export default function RequestModal( props ) {

	const {
		children,
		type,
		title,
		action,
		params,
		item,
		entity,
		endpoint = window.app.endpoints.requests[ type ] ?? window.app.baseUrl,
	} = props;

	const [ modal, setModal ] = useState( false );

	const data = {
		action: action,
	}
	if ( params.item ) {
		data[ params.item ] = JSON.stringify( item );
	}
	if ( params.element ) {
		data[ params.element ] = React.useContext( ElementContext ).value;
	}

	const handleClose = () => {
		setModal( false )
	};
	const handleTrigger = ( e ) => {
		e.preventDefault;
		e.stopPropagation;
		openModal();
	};

	const openModal = async () => {
		const modalTitle = title + ( entity ? entity.name ?? '' : '' );

		setModal( {
			title: modalTitle,
			body: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">Loading...</span>
				</Spinner>
			),
			buttonClose: 'Cancel',
			buttonSave: '',
			handleSave: null
		} );

		const response = await fetchPost( endpoint, data );
		if ( response ) {
			setModal( {
				size: 'xl',
				title: modalTitle,
				body: (
					<div><pre>{ JSON.stringify( response, null, 2 ) }</pre></div>
				),
				buttonClose: 'Close',
				buttonSave: '',
			} );
		}
	}

	const triggerProps = {
		onClick: handleTrigger,
		onFocus: e => e.stopPropagation()
	}

	return (
		<>
			{ typeof children === 'function' ? children( triggerProps ) : cloneElement( children, triggerProps ) }
			{ modal &&
				<div
					onKeyDown={e => e.stopPropagation()}
					onClick={e => e.stopPropagation()}
					onFocus={e => e.stopPropagation()}
					onMouseOver={e => e.stopPropagation()}
				>
					<Modal show={ ! isEmpty( modal ) } size={ modal.size ?? 'md' } onHide={ handleClose } centered scrollable>
						<Modal.Header closeButton>
							<Modal.Title>{ modal.title }</Modal.Title>
						</Modal.Header>
						{ modal.body &&
							<Modal.Body>{ modal.body }</Modal.Body>
						}
						<Modal.Footer>
							<Button variant="secondary" onClick={ handleClose }>
								{ modal.buttonClose ?? 'Close' }
							</Button>
							{ modal.buttonSave &&
								<Button variant="primary" disabled={ ! modal.handleSave } onClick={ modal.handleSave }>
									{ modal.buttonSave }
								</Button>
							}
						</Modal.Footer>
					</Modal>
				</div>
			}
		</>
	);
}
