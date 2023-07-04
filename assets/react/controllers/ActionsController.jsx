import React, { useState } from 'react';
import { Badge, Button, ListGroup, Modal, Spinner, Stack } from "react-bootstrap";

import DeleteModal from "../components/modals/DeleteModal";
import FormStatic from "../components/form/FormStatic";

import { isEmpty } from "../utils/conditionals";
import { objectToMappable } from "../utils/format";
import { createRefId } from "../utils/globals";
import { parseForm } from "../utils/form";
import { fetchPost } from "../utils/fetch";

export default function ActionsController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		entity,
		endpoint,
	} = args;

	const parseOrderFromValue = ( value ) => {
		return objectToMappable( value ).map( ( row ) => {
			if ( 'object' !== typeof row ) {
				row = {
					id: row,
				}
			}
			if ( ! row.hasOwnProperty( '_ref' ) ) {
				row._ref = createRefId();
			}
			return row;
		} )
	}

	const [ modal, setModal ] = useState( false );

	const handleClose = () => setModal( false );
	const handleShow = ( data ) => setModal( data );

	const openEditModal = async ( entity ) => {
		setModal( {
			title: 'Edit: ' + entity.name,
			body: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">Loading...</span>
				</Spinner>
			),
			buttonClose: 'Cancel',
			buttonSave: 'Update',
			handleSave: null
		} );

		const response = await fetchPost( endpoint, { action: 'form', id: entity.id } );
		if ( response.html ) {

			setModal( {
				size: 'xl',
				title: 'Edit: ' + entity.name,
				body: (
					<FormStatic id={ entity.id } entity="entity" html={ response.html.content } />
				),
				buttonClose: 'Cancel',
				buttonSave: 'Update',
				handleSave: async () => {
					const response = await saveEntity( entity );
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

	const saveEntity = async ( entity ) => {
		const form = document.querySelector( '#edit_entity_' + entity.id + ' form' );

		const data = parseForm( form );
		data.action = 'edit';
		data.id = entity.id;

		return await fetchPost( endpoint, data );
	}

	const deleteEntity = async ( entity, ref ) => {

	}

	return (
		<div onClick={ ( e ) => { e.preventDefault(); e.stopPropagation(); } }>
			<Stack direction="horizontal" gap={2}>
				<DeleteModal callback={ () => deleteEntity( entity, _ref ) } />
			</Stack>
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
		</div>
	);
}
