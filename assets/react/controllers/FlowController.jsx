import React, { useState } from 'react';
import { Button, ListGroup, Modal, Spinner, Stack } from "react-bootstrap";
import Sortable from "../components/Sortable";
import { BiPencil, BiTrash } from "react-icons/bi";
import { isEmpty } from "../utils/conditionals";
import { objectToMappable } from "../utils/format";
import { createRefId } from "../utils/globals";
import SortableIcon from "../components/Sortable/SortableIcon";

export default function FlowController( props ) {
	const {
		args = {},
		value = {},
		onChange,
	} = props;

	const {
		steps,
		endpoint,
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

	const openEditModal = async ( step ) => {
		setModal( {
			title: 'Edit: ' + step.name,
			body: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">Loading...</span>
				</Spinner>
			),
			buttonClose: 'Cancel',
			buttonSave: 'Update',
			handleSave: null
		} );

		const response = await ajax( { action: 'form', id: step.id } );
		if ( response.html ) {

			setModal( {
				size: 'lg',
				title: 'Edit: ' + step.name,
				body: (
					<div dangerouslySetInnerHTML={{ __html: response.html.content }} />
				),
				buttonClose: 'Cancel',
				buttonSave: 'Update',
				handleSave: () => {
					console.log( 'yay' );
				}
			} );
		}
	}

	const openDeleteModal = async ( step ) => {
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

	const saveStep = async ( step ) => {
		const response = await ajax( { action: 'form', id: step.id } );
	}

	const deleteStep = async ( step ) => {
		const response = ajax( { action: 'delete', id: step.id } );
	}

	const ajax = async ( data ) => {
		return (
			await fetch( endpoint, {
				method: "POST",
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify( data ),
			} )
		).json();
	}

	return (
		<Stack gap={0}>
			<ListGroup>
			<Sortable
				setItems={ updateOrder }
				items={
					order.map( item => {
						const { id, ref } = item;
						const { name, description, config, } = steps[ id ];
						const { tasks, conditionals } = config;

						return {
							id: ref,
							value: item,
							component: ListGroup.Item,
							attributes: {
								action: true,
								onClick: ( e ) => {
									e.preventDefault();
									e.stopPropagation();
									openEditModal( steps[ id ] );
								}
							},
							header: (
								<Stack direction="horizontal" gap={3}>
									<SortableIcon></SortableIcon>
									<Stack>
										<span>
											{ name }
										</span>
										{ description &&
										  <small className="text-secondary">{ description }</small>
										}
									</Stack>
									<Stack direction="horizontal" gap={2}>
										<BiPencil variant="primary" onClick={ () => openEditModal( steps[ id ] ) }></BiPencil>
										<ConfirmDelete callback={ () => openDeleteModal( steps[ id ] ) } />
									</Stack>
								</Stack>
							)
						};
					} )
				}
			/>
			</ListGroup>
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
		</Stack>
	);
}
