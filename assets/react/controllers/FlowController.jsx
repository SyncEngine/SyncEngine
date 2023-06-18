import React, { useState } from 'react';
import { Badge, Button, ListGroup, Modal, Spinner, Stack } from "react-bootstrap";
import Sortable from "../components/Sortable";
import SortableIcon from "../components/Sortable/SortableIcon";
import ConfirmDelete from "../components/ConfirmDelete";
import { isEmpty } from "../utils/conditionals";
import { objectToMappable } from "../utils/format";
import { createRefId } from "../utils/globals";
import { parseForm } from "../utils/form";
import { fetchPost } from "../utils/fetch";
import EntityForm from "../components/EntityForm";

export default function FlowController( props ) {
	const {
		args = {},
		value = {},
		onChange,
	} = props;

	const {
		steps, // List of all available steps.
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
	const [ stepRepo, setStepRepo ] = useState( steps );

	const getOrderRefs = () => order.map( item => item.id );
	const getOrderIndex = ( id ) => getOrderRefs().indexOf( id );

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

		const response = await fetchPost( endpoint, { action: 'form', id: step.id } );
		if ( response.html ) {

			setModal( {
				size: 'xl',
				title: 'Edit: ' + step.name,
				body: (
					<EntityForm id={ step.id } entity="step" html={ response.html.content } />
				),
				buttonClose: 'Cancel',
				buttonSave: 'Update',
				handleSave: async () => {
					const response = await saveStep( step );
					if ( response.success ) {
						const newStepRepo = stepRepo;
						newStepRepo[ response.step.id ] = response.step;
						setStepRepo( newStepRepo );
						handleClose();
						return;
					}
					// @todo Handle errors.
					alert( response.error );
				}
			} );
		}
	}

	const saveStep = async ( step ) => {
		const form = document.querySelector( '#edit_step_' + step.id + ' form' );

		const data = parseForm( form );
		data.action = 'edit';
		data.id = step.id;

		return await fetchPost( endpoint, data );
	}

	const deleteStep = async ( step, ref ) => {
		let newOrder = [ ...order ];
		newOrder.splice( getOrderIndex( ref ), 1 );
		updateOrder( newOrder );
	}

	return (
		<Stack gap={0}>
			<ListGroup>
				<Sortable
					setItems={ updateOrder }
					items={
						order.map( item => {
							const { id, ref } = item;
							const step = stepRepo[ id ];
							const { name, description, config, } = step;
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
										openEditModal( step );
									}
								},
								header: (
									<Stack direction="horizontal" gap={3}>
										<SortableIcon></SortableIcon>
										<Stack className="align-self-center">
											<span>
												{ name } <Badge pill bg="step" className="ms-auto">Step #{ id }</Badge>
											</span>
											{ description &&
											  <small className="text-secondary">{ description }</small>
											}
										</Stack>
										{ tasks &&
											<ListGroup dir="horizontal">
												{ tasks.map( ( task ) => {
													return (
														<ListGroup.Item key={ task.id }>
															<Stack direction="horizontal" gap={2}>
																{ task.label ?? task.name ?? '--'}
																<Badge pill bg="task" className="ms-auto">{ task.type }</Badge>
															</Stack>
														</ListGroup.Item>
													);
												} ) }
											</ListGroup>
										}
										<Stack direction="horizontal" gap={2}>
											<ConfirmDelete callback={ () => deleteStep( step, ref ) } />
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
