import React, { cloneElement, useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Spinner, Stack } from 'react-bootstrap';

import useGlobal from '../../../hooks/useGlobal';

import Modal from '../Modal';
import ResponseTabs from '../../services/ResponseTabs';

import { ElementContext } from '../../../context/ElementContext';
import { isEmpty, isString } from '../../../utils/conditions';
import { fetchPost } from '../../../utils/fetch';
import { deepClone, objectToMappable } from '../../../utils/data';
import { parseTagString } from '../../../utils/tags';
import { EntityContext } from '../../../context/EntityContext';
import { suppress } from '../../../utils/events';

export default function RequestModal( props ) {
	const { t } = useTranslation();
	const app = useGlobal();

	const {
		children,
		confirm = true,
		type,
		title = t('Request'),
		action,
		item,
		entity,
		element = React.useContext( ElementContext ),
		endpoint = app.endpoints.requests[ type ] ?? app.endpoints.entities[ type ] ?? app.baseUrl,
	} = props;

	const [ modal, setModal ] = useState( false );

	const getTitle = () => {
		return parseTagString( title, item ?? entity );
	}

	const parseParams = ( params = {} ) => {
		if ( isEmpty( params ) ) {
			params = deepClone( props.params ?? {} );
		}

		if ( ! params.action && action ) {
			params.action = action;
		}

		for ( const key in params ) {
			if ( ! params.hasOwnProperty( key ) ) {
				continue;
			}
			switch ( params[ key ] ) {
				case 'item':
					params[ key ] = JSON.stringify( item );
					break;
				case 'element':
					params[ key ] = element.value;
					break;
				case 'entity':
					params[ key ] = entity ? JSON.stringify( entity ) : null;
					break;
				case 'entityId':
					if ( entity && entity.id ) {
						params[ key ] = entity.id;
					} else {
						// @todo Enhance this.
						params[ key ] = element.parentNode.parentNode.dataset.id;
					}
					break;
				default:
					params[ key ] = params[ key ];
					break;
			}
		}

		return params;
	}

	const openModal = () => {
		if ( confirm ) {

			const customConfirm = isString( confirm ) || React.isValidElement( confirm );

			if ( props.actions ) {

				const actions = React.isValidElement( props.actions ) ? props.actions : (
					<Stack direction="horizontal" gap={2} className="justify-content-center">
						{ objectToMappable( props.actions, 'action' ).map( ( action ) => {
							return <Button
								key={ action.action }
								variant={ action.variant ?? 'primary' }
								onClick={ () => { request( { ...action.params, action: action.action } ) } }
							>
								{ action.title }
							</Button>
						} ) }
					</Stack>
				);

				setModal( {
					title: getTitle(),
					body: <Stack gap={2}>{ customConfirm ? confirm : '' }{ actions }</Stack>,
					buttonClose: t('Cancel'),
					buttonSave: '',
					handleSave: null,
				} );
			} else {
				setModal( {
					title: getTitle(),
					body: customConfirm ? confirm : t('Send request?'),
					buttonClose: t('Cancel'),
					buttonSave: t('Send'),
					handleSave: () => request(),
				} );
			}
		} else {
			request();
		}
	}

	const request = async( params ) => {
		setModal( {
			title: getTitle(),
			body: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">{ t('Loading') }...</span>
				</Spinner>
			),
			buttonClose: t('Cancel'),
			buttonSave: '',
			handleSave: null,
		} );

		const response = await fetchPost( endpoint, parseParams( params ) );
		if ( response ) {

			let content = (
				<>
					{ response.message ?? '' }
					{ response.data && <ResponseTabs data={ response.data } contained /> }
				</>
			);

			if ( entity ) {
				content = <EntityContext.Provider value={ entity }>{ content }</EntityContext.Provider>;
			}

			setModal( {
				contained: true,
				size: 'xl',
				title: getTitle() + ': ' + ( response.success ? t('Success') : t('Error') ),
				body: content,
				buttonClose: t('Close'),
				buttonSave: '',
			} );
		}
	}

	const handleClose = useCallback( () => {
		setModal( false )
	}, [ setModal ] );
	const handleTrigger = ( e ) => {
		suppress( e );
		openModal();
	};
	const triggerProps = {
		onClick: handleTrigger,
		onFocus: e => e.stopPropagation()
	}

	return (
		<>
			{ typeof children === 'function' ? children( triggerProps ) : cloneElement( children, triggerProps ) }
			{ modal &&
				<Modal show={ ! isEmpty( modal ) } size={ modal.size ?? props.size ?? 'md' } onHide={ handleClose } dialogClassName={ modal.contained && 'modal-h-100' } centered scrollable expandable>
					<Modal.Header closeButton expandButton>
						<Modal.Title>{ modal.title }</Modal.Title>
					</Modal.Header>
					{ modal.body &&
						<Modal.Body>{ modal.body }</Modal.Body>
					}
					<Modal.Footer>
						<Button variant="outline-secondary" onClick={ handleClose }>
							{ modal.buttonClose ?? t('Close') }
						</Button>
						{ modal.buttonSave &&
							<Button variant="primary" disabled={ ! modal.handleSave } onClick={ modal.handleSave }>
								{ modal.buttonSave }
							</Button>
						}
					</Modal.Footer>
				</Modal>
			}
		</>
	);
}
