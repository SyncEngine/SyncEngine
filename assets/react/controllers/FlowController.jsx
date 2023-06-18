import React, { useState } from 'react';
import { Accordion, Button, Modal, Spinner, Stack, Tabs } from "react-bootstrap";
import Sortable from "../components/Sortable";
import Card from "react-bootstrap/Card";
import { BiPencil, BiTrash } from "react-icons/bi";
import { isEmpty } from "../utils/conditionals";

export default function FlowController( props ) {
	const {
		args = {},
		value = {},
		onChange,
	} = props;

	const {
		steps,
	} = args;

	const [ order, setOrder ] = useState( value );
	const [ modal, setModal ] = useState( false );

	const handleClose = () => setModal( false );
	const handleShow = ( data ) => setModal( data );

	const updateOrder = ( steps ) => {
		setOrder( order );
		onChange( value );
	}

	const openEditModal = ( step ) => {
		setModal( {
			title: 'Edit: ' + step.name,
			body: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">Loading...</span>
				</Spinner>
			),
			buttonClose: 'Cancel',
			buttonSave: 'Save',
			handleSave: null
		} );
	}

	const openDeleteModal = ( step ) => {
		setModal( {
			title: 'Delete: ' + step.name,
			body: 'Are you sure?',
			buttonClose: 'Cancel',
			buttonSave: 'Delete/Unlink',
			handleSave: () => {
				deleteStep( step );
			}
		} );
	}

	const saveStep = ( step ) => {

	}

	const deleteStep = ( step ) => {

	}

	const openDeleteModal = ( id ) => {

	}

	return (
		<Stack>
			<Sortable
				setItems={ updateOrder }
				items={
					steps.map( step => {
						return {
							id: step.id,
							component: Card,
							attributes: {
								eventKey: step.id,
							},
							body: (
								<Card.Body>
									<Card.Title>{ step.name }</Card.Title>
									<Card.Text>{ step.description }</Card.Text>
									<Button variant="primary" onClick={ () => openEditModal( step ) }><BiPencil></BiPencil></Button>
									<Button variant="danger" onClick={ () => openDeleteModal( step ) }><BiTrash></BiTrash></Button>
								</Card.Body>
							)
						};
					} )
				}
			/>
			{ modal &&
				<Modal show={ ! isEmpty( modal ) } onHide={ handleClose }>
					<Modal.Header closeButton>
						<Modal.Title>{ modal.title }</Modal.Title>
					</Modal.Header>
					<Modal.Body>{ modal.body }</Modal.Body>
					<Modal.Footer>
						<Button variant="secondary" onClick={ handleClose }>
							{ modal.buttnClose ?? 'Close' }
						</Button>
						<Button variant="primary" disabled={ ! modal.handleSave } onClick={ modal.handleSave }>
							{ modal.buttonSave }
						</Button>
					</Modal.Footer>
				</Modal>
			}
		</Stack>
	);
}
