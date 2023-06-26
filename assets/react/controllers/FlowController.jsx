import React, { useState } from 'react';
import { Badge, Button, InputGroup, ListGroup, Modal, Spinner, Stack } from "react-bootstrap";

import Sortable from "../components/services/Sortable";
import SortableIcon from "../components/services/Sortable/SortableIcon";
import ConfirmDelete from "../components/form/ConfirmDelete";
import FormStatic from "../components/form/FormStatic";

import { isEmpty } from "../utils/conditionals";
import { objectToMappable } from "../utils/format";
import { createRefId } from "../utils/globals";
import { parseForm } from "../utils/form";
import { fetchPost } from "../utils/fetch";
import StepSelector from "../components/form/SelectStep";

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

	const [ order, setOrder ] = useState( parseOrderFromValue( value ) );
	const [ modal, setModal ] = useState( false );
	const [ stepRepo, setStepRepo ] = useState( steps );

	const getOrderRefs = () => order.map( item => item._ref );
	const getOrderIndex = ( _ref ) => getOrderRefs().indexOf( _ref );

	const handleClose = () => setModal( false );
	const handleShow = ( e ) => {
		e.preventDefault();
		e.stopPropagation();
		openModal( {} );
	};

	const updateOrder = ( order ) => {
		setOrder( order );
		onChange( order.map( ( item ) => item.id ) );
	}

	const openModal = async ( step ) => {
		let action = 'Edit',
			confirm = 'Update';
		if ( ! step.id ) {
			step = {
				name: 'Step',
				id: 'new',
			};
			action = 'New';
			confirm = 'Create';
		}

		setModal( {
			title: action + ': ' + step.name,
			body: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">Loading...</span>
				</Spinner>
			),
			buttonClose: 'Cancel',
			buttonSave: confirm,
			handleSave: null
		} );

		const response = await fetchPost( endpoint, { action: 'form', id: step.id } );
		if ( response.html ) {

			setModal( {
				size: 'xl',
				title: action + ': ' + step.name,
				body: (
					<FormStatic id={ step.id } entity="step" html={ response.html.content } />
				),
				buttonClose: 'Cancel',
				buttonSave: confirm,
				handleSave: async () => {
					const response = await saveStep( step );
					if ( response ) {
						handleClose();
					}
				}
			} );
		}
	}

	const saveStep = async ( step ) => {
		const form = document.querySelector( '#form_step_' + step.id + ' form' );
		const action = ( 'new' === step.id ) ? 'create' : 'edit';

		const data = parseForm( form );
		data.action = action;
		data.id = step.id;

		const response = await fetchPost( endpoint, data );
		if ( response.success ) {
			const newStepRepo = stepRepo;
			const id = parseInt( response.step.id, 10 );
			newStepRepo[ id ] = response.step;
			setStepRepo( newStepRepo );
			if ( 'create' === action ) {
				addStep( id );
			}
			return true;
		}
		// @todo Handle errors.
		alert( response.error );
		return false;
	}

	const addStep = ( id ) => {
		id = parseInt( id, 10 );
		let newOrder = [ ...order ];
		newOrder.push( { id: id , _ref: createRefId() } );
		updateOrder( newOrder );
	}

	const deleteStep = async ( _ref ) => {
		let newOrder = [ ...order ];
		newOrder.splice( getOrderIndex( _ref ), 1 );
		updateOrder( newOrder );
	}

	return (
		<Stack gap={2} className="mt-2" onClick={ ( e ) => { e.preventDefault(); e.stopPropagation(); } }>
			<ListGroup>
				<Sortable
					setItems={ updateOrder }
					items={
						order.map( item => {
							const { id, _ref } = item;
							const step = stepRepo[ id ];
							const { name, description, config, } = step;
							const { tasks, conditionals } = config;

							return {
								id: _ref,
								value: item,
								component: ListGroup.Item,
								attributes: {
									action: true,
									onClick: ( e ) => {
										e.preventDefault();
										e.stopPropagation();
										openModal( step );
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
														<ListGroup.Item key={ task._ref }>
															<Stack direction="horizontal" gap={2}>
																{ task.label ?? task.name ?? '--'}
																<Badge pill bg="task" className="ms-auto">{ task._class }</Badge>
															</Stack>
														</ListGroup.Item>
													);
												} ) }
											</ListGroup>
										}
										<Stack direction="horizontal" gap={2}>
											<ConfirmDelete callback={ () => deleteStep( _ref ) } />
										</Stack>
									</Stack>
								)
							};
						} )
					}
				/>
				<InputGroup className="p-2 border border-top-0">
					<Button variant="outline-step" onClick={ handleShow }>
						Create step
					</Button>
					<StepSelector options={ steps } label="Add step" selectClass="border-step-subtle" onChange={ addStep } />
				</InputGroup>
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
