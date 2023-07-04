import React, { useState } from 'react';
import { Badge, Button, InputGroup, ListGroup, Modal, Spinner, Stack } from "react-bootstrap";

import FormStatic from "../components/form/FormStatic";
import SelectStep from "../components/form/SelectStep";
import Repeatable from "../components/services/Repeatable";

import { isEmpty } from "../utils/conditionals";
import { objectToMappable } from "../utils/format";
import { createRefId } from "../utils/globals";
import { parseForm } from "../utils/form";
import { fetchPost } from "../utils/fetch";

export default function FlowController( props ) {

	const {
		args = {},
		value = [],
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

	const getForm = ( step ) => {
		return document.querySelector( '#form_step_' + step.id + ' form' );
	}

	const handleClose = ( form ) => {
		if ( form ) {
			// @todo Check for changes.
			form.dispatchEvent( new Event( 'removed' ) );
		}
		setModal( false )
	};
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
				step: step,
				size: 'xl',
				title: action + ': ' + step.name,
				body: (
					<FormStatic id={ step.id } entity="step" html={ response.html.content } />
				),
				buttonClose: 'Cancel',
				buttonSave: confirm,
				handleClose: () => {
					handleClose( getForm( step ) );
				},
				handleSave: async () => {
					const response = await saveStep( step );
					if ( response ) {
						handleClose( getForm( step ) );
					}
				}
			} );
		}
	}

	const saveStep = async ( step ) => {
		const form = getForm( step );
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
			// @todo Centralized method to handle window unload checks.
			form.dispatchEvent( new Event( 'submitted' ) );
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

	const items = order.map( item => {
		const { id, _ref } = item;
		const step = stepRepo[ id ];
		const { name, description, config, } = step;
		const { tasks, conditionals } = config;

		const body = tasks &&
			<ListGroup dir="horizontal">
				{ tasks.map( ( task ) => {
					return (
						<ListGroup.Item key={task._ref}>
							<Stack direction="horizontal" gap={2}>
								{task.label ?? task.name ?? '--'}
								<Badge pill bg="task" className="text-bg-task ms-auto">{task._class}</Badge>
							</Stack>
						</ListGroup.Item>
					);
				} )}
			</ListGroup>;

		return {
			_ref: _ref,
			value: item,
			onClick: ( e ) => {
				e.preventDefault();
				e.stopPropagation();
				openModal( step );
			},
			label: (
				<>{name} <Badge pill bg="step" className="text-bg-step ms-auto">Step #{id}</Badge></>
			),
			actions: { 'delete': deleteStep },
			description: description,
			body: body
		}
	} );

	const toolbar = (
		<>
			<Button variant="outline-step" onClick={ handleShow }>
				Create step
			</Button>
			<SelectStep options={ steps } label="Add step" selectClass="border-step-subtle" onChange={ addStep } />
		</>
	);

	return (
		<Stack gap={2} className="mt-2" onClick={ ( e ) => { e.preventDefault(); e.stopPropagation(); } }>
			<Repeatable items={ items } toolbar={ toolbar } inline={ true } sortable={ true } reorderCallback={ updateOrder } />
			{ modal &&
				<div
					onKeyDown={e => e.stopPropagation()}
					onClick={e => e.stopPropagation()}
					onFocus={e => e.stopPropagation()}
					onMouseOver={e => e.stopPropagation()}
				>
					<Modal show={ ! isEmpty( modal ) } size={ modal.size ?? 'md' } onHide={ modal.handleClose ?? handleClose } centered scrollable>
						<Modal.Header closeButton>
							<Modal.Title>{ modal.title }</Modal.Title>
						</Modal.Header>
						{ modal.body &&
							<Modal.Body>{ modal.body }</Modal.Body>
						}
						<Modal.Footer>
							<Button variant="secondary" onClick={ modal.handleClose ?? handleClose }>
								{ modal.buttonClose ?? 'Close' }
							</Button>
							<Button variant="primary" disabled={ ! modal.handleSave } onClick={ modal.handleSave }>
								{ modal.buttonSave }
							</Button>
						</Modal.Footer>
					</Modal>
				</div>
			}
		</Stack>
	);
}
