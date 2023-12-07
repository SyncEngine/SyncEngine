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
		createCallback = 'create' === action ? callback : null,
		editCallback = 'edit' === action ? callback : null,
		endpoint = window.SyncEngine.endpoints.entities[ type ] ?? window.SyncEngine.baseUrl,
		triggerRef,
	} = props;

	// Edit callback is required for savable to be allowed.
	// @todo Make this optional? I don't see any use case for now..
	const savable = editCallback && props.savable;

	const entity = props.entity ?? {
		name: props.name ?? '',
		id: props.id ?? 'new',
	};

	const [ isNew ] = useState( 'new' === entity.id );

	// @todo use state for entity?
	const [ _unused, entityCallbacks ] = useEntity( type );

	const [ modal, setModal ] = useState( false );
	const [ loading, setLoading ] = useState( false );

	const formRef = useRef( { entity: entity } );

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
						parentRef={ formRef }
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
	 * @param {Object} params
	 */
	const save = async ( params ) => {
		setLoading( true );

		if ( ! params.form ) {
			return false;
		}

		const form = params.form;
		const data = parseForm( form );

		let close = ( savable && params.close ) ?? true;

		data.action = 'edit';
		data.id     = formRef.current.entity.id;

		const response = await fetchPost( endpoint, data );

		if ( response.success ) {
			if ( response.entity ) {
				formRef.current.entity = response.entity;
				//updateModal();
				entityCallbacks.update( response.entity );
				if ( 'new' === data.id ) {
					if ( createCallback ) {
						createCallback( entityCallbacks.get( response.entity.id, true ), response );
					}
				} else if ( editCallback ) {
					editCallback( entityCallbacks.get( response.entity.id, true ), response );
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
		const form = formRef.current.element ?? null;
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
		formRef.current.submit( save, { close: close } );
	};

	const getModalLabels = ( entity ) => {
		let labels,
			entitySuffix = ' ' + ( entity._class || window.SyncEngine.labels[ type ] || type );
		if ( entity.name ) {
			entitySuffix += ': ' + entity.name;
		}

		if ( 'new' === entity.id ) {
			labels = {
				'title': t( 'New' ) + entitySuffix,
				'buttonClose': t('Cancel'),
				'buttonSave': t( 'Create and continue' ),
				'buttonUpdate': t( 'Create' ),
			}
		} else {
			labels = {
				'title': t( 'Edit' ) + entitySuffix,
				'buttonClose': t('Cancel'),
				'buttonSave': t( 'Save' ),
				'buttonUpdate': t( 'Update' ),
			}
		}

		return labels;
	}

	const labels = modal && getModalLabels( formRef.current.entity );
	if ( labels ) {
		if ( isSet( modal.buttonSave ) && ! modal.buttonSave ) {
			delete labels.buttonSave;
		}
		if ( isSet( modal.buttonUpdate ) && ! modal.buttonUpdate ) {
			delete labels.buttonUpdate;
		}
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
							{ ( savable && labels.buttonSave ) &&
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
