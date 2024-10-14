import React, { cloneElement, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Spinner } from 'react-bootstrap';

import useEntity from '../../../hooks/useEntity';

import Modal from '../Modal';
import FormStatic from '../../form/FormStatic';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

import { isEmpty, isId, isSet } from '../../../utils/conditions';
import { parseForm } from '../../../utils/form';
import { fetchPost } from '../../../utils/fetch';
import useGlobal from '../../../hooks/useGlobal';
import { debug } from '../../../utils/globals';
import Icon from '../../partials/Icon';
import { suppress } from '../../../utils/events';

export default function EntityModal( props ) {
	const { t } = useTranslation();
	const app = useGlobal();

	const {
		children,
		type,
		action = 'edit',
		callback,
		createCallback = 'create' === action ? callback : null,
		editCallback = 'edit' === action ? callback : null,
		endpoint = app.endpoints.entities[ type ] ?? app.baseUrl,
		triggerRef,
	} = props;

	// Edit callback is required for savable to be allowed.
	// @todo Make this optional? I don't see any use case for now..
	const savable = editCallback && props.savable;

	const [ _unused, entityCallbacks ] = useEntity( type );

	const [ modal, setModal ] = useState( false );
	const [ loading, setLoading ] = useState( '' );

	const formRef = useRef( {} );

	const openModal = async ( response ) => {

		formRef.current.entity = props.entity ?? {
			name: props.name || '',
			id: props.id || 'new',
		};

		if ( ! response ) {
			setModal( {
				body: <LoadingPlaceholder/>,
				buttonSave: false,
				buttonUpdate: false,
				handleSubmit: null,
			} );

			response = await fetchPost( endpoint, { action: action, id: formRef.current.entity.id } );
		}

		// Re-init entity.
		if ( response.entity ) {
			formRef.current.entity = response.entity;
		}

		if ( response.html ) {

			setModal( {
				size: 'xl',
				body: (
					<FormStatic
						type={ type }
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
		if ( ! params.form ) {
			return false;
		}

		let close = ( savable && params.close ) ?? true;
		setLoading( close ? 'update' : 'save' );

		const form = params.form;
		const data = parseForm( form );

		data.id     = formRef.current.entity.id;
		data.action = action;

		if ( isId( data.id ) ) {
			data.action = 'edit';
		} else {
			// Only allow `create` action and only allow with empty ID.
			if ( 'create' !== action || ! isEmpty( data.id ) ) {
				setLoading( false );
				if ( close ) {
					handleClose();
				}

				debug( 'Invalid ID', data );
				return;
			}
		}

		const response = await fetchPost( endpoint, data );

		if ( response.success ) {
			if ( response.entity ) {
				formRef.current.entity = response.entity;
				//updateModal();
				entityCallbacks.update( response.entity );
				if ( 'new' === data.id || ! data.id ) {
					if ( createCallback ) {
						createCallback( await entityCallbacks.get( response.entity.id, true ), response );
					}
				} else if ( editCallback ) {
					editCallback( await entityCallbacks.get( response.entity.id, true ), response );
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
		suppress( e );
		openModal();
	};

	const handleSubmit = ( close = true ) => {
		formRef.current.submit( save, { close: close } );
	};

	const getModalLabels = ( entity ) => {
		let labels,
			entitySuffix = ' ' + ( entity._class || app.labels[ type ] || type );
		if ( entity.name ) {
			entitySuffix += ': ' + entity.name;
		}

		if ( ! entity.id || 'new' === entity.id ) {
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
				<Modal show={ ! isEmpty( modal ) } size={ modal.size ?? 'md' } onHide={ handleClose } centered scrollable expandable>
					<Modal.Header closeButton expandButton className={ type ? "text-" + type + "-emphasis border-" + type : '' }>
						<Modal.Title>
							<Icon className="me-3" icon={ app.icons.hasOwnProperty( type ) ? app.icons[ type ] : 'edit' } />
							{ modal.title ?? labels.title }
						</Modal.Title>
					</Modal.Header>
					{ modal.body &&
						<Modal.Body className={ loading && "opacity-75" }>{ modal.body }</Modal.Body>
					}
					<Modal.Footer>
						<Button variant="outline-secondary" onClick={ handleClose }>
							{ modal.buttonClose ?? labels.buttonClose ?? t('Close') }
						</Button>
						{ ( savable && labels.buttonSave ) &&
							<Button
								variant="outline-primary"
								disabled={ ! modal.handleSubmit || loading }
								onClick={ () => modal.handleSubmit( false ) }
							>
								{ 'save' === loading ?
									<Spinner animation="grow" size="sm" className="me-2" />
									:
									<Icon icon="save" className="me-2" />
								}
								{ labels.buttonSave }
							</Button>
						}
						{ labels.buttonUpdate &&
							<Button
								variant="primary"
								disabled={ ! modal.handleSubmit || loading }
								onClick={ () => modal.handleSubmit( true ) }
							>
								{ 'update' === loading ?
									<Spinner animation="grow" size="sm" className="me-2" />
									:
									<Icon icon="update" className="me-2" />
								}
								{ labels.buttonUpdate }
							</Button>
						}
					</Modal.Footer>
				</Modal>
			}
		</>
	);
}
