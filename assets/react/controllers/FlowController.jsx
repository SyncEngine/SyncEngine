import React, { useState } from 'react';
import { Accordion, Button, Modal, Spinner, Stack, Tabs } from "react-bootstrap";
import Sortable from "../components/Sortable";
import Card from "react-bootstrap/Card";
import { BiPencil, BiTrash } from "react-icons/bi";
import { isEmpty } from "../utils/conditionals";
import { objectToMappable } from "../utils/format";
import { createRefId } from "../utils/globals";

export default function FlowController( props ) {
	const {
		args = {},
		value = {},
		onChange,
	} = props;

	const {
		steps,
	} = args;

	const parseValue = ( value ) => {
		return objectToMappable( value ).map( ( row ) => {
			if ( 'object' !== typeof row ) {
				row = {
					id: row,
				}
			}
			if ( ! row.hasOwnProperty( 'ref' ) ) {
				row.ref = createRefId();
			}
			return row;
		} )
	}

	const [ order, setOrder ] = useState( parseValue( value ) );
	const [ modal, setModal ] = useState( false );

	const handleClose = () => setModal( false );
	const handleShow = ( data ) => setModal( data );

	const updateOrder = ( order ) => {
		setOrder( order );
		onChange( order.map( ( item ) => item.id ) )
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
					order.map( item => {
						const {
							id,
							ref
						} = item;

						return {
							id: ref,
							value: item,
							component: Card,
							body: (
								<Card.Body>
									<Card.Title>{ steps[ id ].name }</Card.Title>
									<Card.Text>{ steps[ id ].description }</Card.Text>
									<Button variant="primary" onClick={ () => openEditModal( steps[ id ] ) }><BiPencil></BiPencil></Button>
									<Button variant="danger" onClick={ () => openDeleteModal( steps[ id ] ) }><BiTrash></BiTrash></Button>
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
