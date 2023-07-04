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
		title = 'Request',
		action,
		params,
		item,
		entity,
		element = React.useContext( ElementContext ),
		endpoint = window.app.endpoints.requests[ type ] ?? window.app.baseUrl,
	} = props;

	const [ modal, setModal ] = useState( false );

	const getTitle = () => {
		return title + ( entity ? entity.name ?? '' : '' );
	}

	const getData = () => {
		const data = {
			action: action,
		}
		if ( params.item ) {
			data[ params.item ] = JSON.stringify( item );
		}
		if ( params.element ) {
			data[ params.element ] = element.value;
		}
		if ( params.entityId ) {
			if ( entity && entity.id ) {
				data[ params.entityId ] = entity.id;
			} else {
				// @todo Enhance this.
				data[ params.entityId ] = element.parentNode.parentNode.dataset.id;
			}
		}
		return data;
	}

	const handleClose = () => {
		setModal( false )
	};
	const handleTrigger = ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		openModal();
	};

	const openModal = async () => {

		setModal( {
			title: getTitle(),
			body: (
				"Send request?"
			),
			buttonClose: 'Cancel',
			buttonSave: 'Send',
			handleSave: request,
		} );
	}

	const request = async () => {
		console.log( modal );
		setModal( {
			title: getTitle(),
			body: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">Loading...</span>
				</Spinner>
			),
			buttonClose: 'Cancel',
			buttonSave: '',
			handleSave: null
		} );

		const response = await fetchPost( endpoint, getData() );
		if ( response ) {
			setModal( {
				size: 'xl',
				title: getTitle(),
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
