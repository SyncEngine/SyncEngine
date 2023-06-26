import React, { useState } from 'react';
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
		action,
		callback,
		// @todo Use globals?
		endpoint = '/' + type + '/' + 'json',
	} = props;

	const entity = {
		name: name ?? 'New',
		id: id ?? 'new',
	};

	const [ modal, setModal ] = useState( false );

	const handleClose = () => setModal( false );
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
				handleSave: async () => {
					const response = await save( entity );
					if ( response.success ) {
						handleClose();
						return;
					}
					// @todo Handle errors.
					alert( response.error );
				}
			} );
		}
	}

	const save = async ( entity ) => {
		const form = document.querySelector( '#form_' + type + '_' + entity.id + ' form' );

		const data = parseForm( form );
		data.action = 'edit';
		data.id = entity.id;

		const response = await fetchPost( endpoint, data );
		if ( response.success ) {
			callback( response[ type ], response );
			return true;
		}
		return false;
	}

	return (
		<>
			<div onClick={ handleTrigger }>{ children }</div>
			{ modal &&
			  <Modal show={ ! isEmpty( modal ) } size={ modal.size ?? 'md' } onHide={ handleClose } centered>
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
					  <Button variant="primary" disabled={ ! modal.handleSave } onClick={ modal.handleSave }>
						  { modal.buttonSave }
					  </Button>
				  </Modal.Footer>
			  </Modal>
			}
		</>
	);
}
