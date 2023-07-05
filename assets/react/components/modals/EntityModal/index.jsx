import React, { useState, cloneElement } from 'react';
import { Button, Modal, Spinner } from "react-bootstrap";

import FormStatic from "../../form/FormStatic";

import { isEmpty } from "../../../utils/conditionals";
import { parseForm } from "../../../utils/form";
import { fetchPost } from "../../../utils/fetch";

export default function EntityModal( props ) {

	const {
		children,
		type,
		id,
		name,
		action, // @todo remove or use.
		callback,
		endpoint = window.app.endpoints.entities[ type ] ?? window.app.baseUrl,
	} = props;

	const entity = {
		name: name ?? 'New',
		id: id ?? 'new',
	};

	const [ modal, setModal ] = useState( false );

	const getForm = () => {
		return document.querySelector( '#form_' + type + '_' + entity.id + ' form' );
	}

	const handleClose = () => {
		const form = getForm();
		if ( form ) {
			// @todo Check for changes.
			form.dispatchEvent( new Event( 'removed' ) );
		}
		setModal( false )
	};
	const handleTrigger = ( e ) => {
		e.preventDefault;
		e.stopPropagation;
		openModal();
	};

	const openModal = async () => {
		let action = 'Edit',
			confirm = 'Update';
		if ( 'new' === entity.id ) {
			action = 'New';
			confirm = 'Create';
		}

		setModal( {
			title: action + ': ' + entity.name,
			body: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">Loading...</span>
				</Spinner>
			),
			buttonClose: 'Cancel',
			buttonSave: '',
			handleSave: null
		} );

		const response = await fetchPost( endpoint, { action: 'form', id: entity.id } );
		if ( response.html ) {

			setModal( {
				size: 'xl',
				title: action + ': ' + entity.name,
				body: (
					<FormStatic id={ entity.id } entity={ type } html={ response.html.content } />
				),
				buttonClose: 'Cancel',
				buttonSave: confirm,
				handleSave: () => {
					const form = getForm();
					form.addEventListener( 'submit', function ( e ) {
						e.preventDefault();
						if ( this.checkValidity() ) {
							save( entity );
						}
						this.reportValidity();
					}, false );
					form.dispatchEvent( new Event( 'submit' ) );
				}
			} );
		}
	}

	const save = async ( entity ) => {
		const form = getForm();
		const data = parseForm( form );
		data.action = 'edit';
		data.id = entity.id;

		const response = await fetchPost( endpoint, data );
		if ( response.success ) {
			callback( response[ type ], response );
			// @todo Centralized method to handle window unload checks.
			form.dispatchEvent( new Event( 'submitted' ) );

			handleClose();
			return;
		}

		// @todo Handle errors.
		alert( response.error );
	}

	const triggerProps = {
		onClick: handleTrigger,
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
