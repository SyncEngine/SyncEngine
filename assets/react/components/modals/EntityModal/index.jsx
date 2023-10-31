import React, { useState, cloneElement } from 'react';
import { Button, Modal } from "react-bootstrap";

import FormStatic from "../../form/FormStatic";
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

import { isEmpty } from "../../../utils/conditionals";
import { parseForm } from "../../../utils/form";
import { fetchPost } from "../../../utils/fetch";
import useEntity from '../../../hooks/useEntity';
import ModalWrapper from '../ModelWrapper';

export default function EntityModal( props ) {

	const {
		children,
		type,
		action = 'edit',
		callback,
		endpoint = window.app.endpoints.entities[ type ] ?? window.app.baseUrl,
		triggerRef,
	} = props;

	const entity = props.entity ?? {
		name: props.name ?? 'New',
		id: props.id ?? 'new',
	};

	const [ modal, setModal ] = useState( false );
	const [ loading, setLoading ] = useState( false );

	const [ _unused, entityCallbacks ] = useEntity( type );

	const getForm = () => {
		return document.querySelector( '#form_' + type + '_' + entity.id + ' form' );
	}

	const handleClose = () => {
		const form = getForm();
		if ( form ) {
			// @todo Check for changes.
			form.dispatchEvent( new Event( 'removed' ) );
		}
		setLoading( false );
		setModal( false );
	};
	const handleTrigger = ( e ) => {
		e.preventDefault;
		e.stopPropagation;
		openModal();
	};

	const openModal = async () => {
		let actionTitle, confirm;
		switch ( action ) {
			case 'export':
				actionTitle = 'Export';
				break;
			case 'delete':
				actionTitle = 'Delete';
				break;
			default:
				actionTitle = 'Edit';
				confirm = 'Update';
				if ( 'new' === entity.id ) {
					actionTitle = 'New';
					confirm = 'Create';
				} else {
					// @todo Labels hook?
					actionTitle += ' ' + ( window.app.labels[ type ] ?? type );
				}
				break;
		}

		setModal( {
			title: actionTitle + ': ' + entity.name,
			body: <LoadingPlaceholder/>,
			buttonClose: 'Cancel',
			buttonSave: '',
			handleSave: null
		} );

		const response = await fetchPost( endpoint, { action: action, id: entity.id } );
		if ( response.html ) {

			setModal( {
				size: 'xl',
				title: actionTitle + ': ' + entity.name,
				body: (
					<FormStatic id={ entity.id } type={ type } entity={ response.entity ?? entity } html={ response.html.content } />
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

		} else if ( response.data || response.hasOwnProperty( action ) ) {
			const data = response[ action ] ?? response.data;

			setModal( {
				size: 'xl',
				title: actionTitle + ': ' + entity.name,
				body: (
					<>
						<pre>{ JSON.stringify( response[ action ] ?? response.data, null, 2 ) }</pre>
					</>
				),
				buttonClose: 'Close',
				buttonSave: confirm,
				handleSave: null,
			} );
		}
	}

	/**
	 * @param {Object} entity
	 */
	const save = async ( entity ) => {
		setLoading( true );

		const form = getForm();
		const data = parseForm( form );

		data.action = 'edit';
		data.id = entity.id;

		const response = await fetchPost( endpoint, data );

		if ( response.success ) {
			if ( callback && response.entity ) {
				entityCallbacks.update( response.entity );
				callback( entityCallbacks.get( response.entity.id, true ), response );
			}
			// @todo Centralized method to handle window unload checks.
			form.dispatchEvent( new Event( 'submitted' ) );

			setLoading( false );
			handleClose();
			return;
		}

		// @todo Handle errors.
		setLoading( false );
		alert( response.error );
	}

	const triggerProps = {
		onClick: handleTrigger,
	}

	// Set parent modal trigger callback.
	if ( triggerRef ) {
		triggerRef.callback = openModal;
	}

	return (
		<>
			{ typeof children === 'function' ? children( triggerProps ) : cloneElement( children, triggerProps ) }
			{ modal &&
				<ModalWrapper>
					<Modal show={ ! isEmpty( modal ) } size={ modal.size ?? 'md' } onHide={ handleClose } centered scrollable>
						<Modal.Header closeButton className={ type ? 'bg-' + type + '-subtle' : '' }>
							<Modal.Title>{ modal.title }</Modal.Title>
						</Modal.Header>
						{ modal.body &&
							<Modal.Body className={ loading && "opacity-50" }>{ modal.body }</Modal.Body>
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
				</ModalWrapper>
			}
		</>
	);
}
