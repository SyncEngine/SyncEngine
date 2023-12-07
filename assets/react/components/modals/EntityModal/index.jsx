import React, { useState, cloneElement, useRef, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Modal } from "react-bootstrap";

import useEntity from '../../../hooks/useEntity';

import ModalWrapper from '../ModelWrapper';
import FormStatic from "../../form/FormStatic";
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

import { isEmpty, isSet } from '../../../utils/conditionals';
import { parseForm } from "../../../utils/form";
import { fetchPost } from "../../../utils/fetch";

export default function EntityModal( props ) {
	const { t } = useTranslation();

	const {
		children,
		type,
		action = 'edit',
		callback,
		endpoint = window.SyncEngine.endpoints.entities[ type ] ?? window.SyncEngine.baseUrl,
		triggerRef,
	} = props;

	let entity = props.entity ?? {
		name: props.name ?? '',
		id: props.id ?? 'new',
	};

	const [ isNew ] = useState( 'new' === entity.id );

	// @todo use state for entity?
	const [ _unused, entityCallbacks ] = useEntity( type );

	const [ modal, setModal ] = useState( false );
	const [ loading, setLoading ] = useState( false );

	const getForm = () => {
		return document.querySelector( '#form_' + type + '_' + ( isNew ? 'new' : entity.id ) + ' form' );
	}

	const openModal = async ( response ) => {

		if ( ! response ) {
			setModal( {
				body: <LoadingPlaceholder/>,
				buttonSave: false,
				buttonUpdate: false,
				handleSubmit: null,
			} );

			response = await fetchPost( endpoint, { action: action, id: entity.id } );
		}

		if ( response.html ) {

			setModal( {
				size: 'xl',
				body: (
					<FormStatic
						id={ ( isNew ? 'new' : entity.id ) }
						type={ type }
						entity={ entity }
						html={ response.html.content }
					/>
				),
				handleSubmit: handleSubmit
			} )

		} else if ( response.data || response.hasOwnProperty( action ) ) {
			const data = response[ action ] ?? response.data;

			setModal( {
				size: 'xl',
				body: (
					<>
						<pre>{ JSON.stringify( response[ action ] ?? response.data, null, 2 ) }</pre>
					</>
				),
				buttonClose: t('Close'),
				handleSubmit: null,
			} );
		}
	}

	/**
	 * @param {Object} updatedEntity
	 * @param {boolean} close
	 */
	const save = async ( updatedEntity, close ) => {
		setLoading( true );

		const form = getForm();
		const data = parseForm( form );

		data.action = 'edit';
		if ( 'new' !== entity.id && 'new' === updatedEntity.id ) {
			data.id = entity.id;
		} else {
			data.id = updatedEntity.id;
		}

		const response = await fetchPost( endpoint, data );

		if ( response.success ) {
			if ( response.entity ) {
				entity = response.entity;
				//updateModal();
				if ( callback ) {
					entityCallbacks.update( response.entity );
					callback( entityCallbacks.get( response.entity.id, true ), response );
				}
			} else {
				// No entity info, close window.
				// @todo Error?
				close = true;
			}
			// @todo Centralized method to handle window unload checks.
			form.dispatchEvent( new Event( 'submitted' ) );

			setLoading( false );
			if ( close ) {
				handleClose();
			}
			return;
		}

		// @todo Handle errors.
		setLoading( false );
		alert( response.error );
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

	const handleSubmit = ( close = true ) => {
		const form = getForm();
		const listener = function ( e ) {
			e.preventDefault();
			if ( this.checkValidity() ) {
				form.removeEventListener( 'submit', listener );
				save( entity, close );
			}
			this.reportValidity();
		};
		form.removeEventListener( 'submit', listener );
		form.addEventListener( 'submit', listener, false );
		form.dispatchEvent( new Event( 'submit' ) );
	};

	const getModalLabels = ( entity ) => {
		let entitySuffix = ' ' + ( entity._class || window.SyncEngine.labels[ type ] || type );
		if ( entity.name ) {
			entitySuffix += ': ' + entity.name;
		}

		if ( 'new' === entity.id ) {
			return {
				'title': t( 'New' ) + entitySuffix,
				'buttonClose': t('Cancel'),
				'buttonSave': t( 'Create and continue' ),
				'buttonUpdate': t( 'Create' ),
			}
		}
		return {
			'title': t( 'Edit' ) + entitySuffix,
			'buttonClose': t('Cancel'),
			'buttonSave': t( 'Save' ),
			'buttonUpdate': t( 'Update' ),
		}
	}

	const labels = getModalLabels( entity );
	if ( isSet( modal.buttonSave ) ) {
		delete modal.buttonSave;
	}
	if ( isSet( modal.buttonUpdate ) ) {
		delete modal.buttonUpdate;
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
							<Modal.Title>{ modal.title ?? labels.title }</Modal.Title>
						</Modal.Header>
						{ modal.body &&
							<Modal.Body className={ loading && "opacity-50" }>{ modal.body }</Modal.Body>
						}
						<Modal.Footer>
							<Button variant="secondary" onClick={ handleClose }>
								{ modal.buttonClose ?? labels.buttonClose ?? t('Close') }
							</Button>
							{ labels.buttonSave &&
								<Button variant="success" disabled={ ! modal.handleSubmit } onClick={ () => modal.handleSubmit( false ) }>
									{ labels.buttonSave }
								</Button>
							}
							{ labels.buttonUpdate &&
								<Button variant="primary" disabled={ ! modal.handleSubmit } onClick={ () => modal.handleSubmit( true ) }>
									{ labels.buttonUpdate }
								</Button>
							}
						</Modal.Footer>
					</Modal>
				</ModalWrapper>
			}
		</>
	);
}
