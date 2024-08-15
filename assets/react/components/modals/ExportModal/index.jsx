import React, { cloneElement, useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Spinner } from 'react-bootstrap';

import ExportModalContent from './ExportContent';
import Modal from '../Modal';

import { isEmpty } from '../../../utils/conditions';
import { fetchPost } from '../../../utils/fetch';
import useGlobal from '../../../hooks/useGlobal';

export default function ExportModal( props ) {
	const { t } = useTranslation();
	const app = useGlobal();

	const {
		children,
		type,
		id,
		name,
		action, // @todo remove or use.
		endpoint = app.endpoints.entities[ type ] ?? app.baseUrl,
	} = props;

	const entity = {
		name: name ?? '',
		id: id ?? null,
	};

	const [ modal, setModal ] = useState( false );

	const handleClose = useCallback( () => {
		setModal( false )
	}, [ setModal ] );

	const handleTrigger = useCallback( ( e ) => {
		e.preventDefault;
		e.stopPropagation;
		openModal();
	}, [ openModal ] );

	const openModal = useCallback( async () => {
		let modalTitle = t('Export');

		// @todo Labels hook?
		modalTitle += ' ' + ( entity._class || app.labels[ type ] || type );

		if ( entity.name ) {
			modalTitle += ': ' + entity.name;
		}

		setModal( {
			title: modalTitle,
			body: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">Loading...</span>
				</Spinner>
			),
			buttonClose: t('Cancel'),
			buttonSave: '',
			handleSave: null
		} );

		const response = await fetchPost( endpoint, { action: action, id: entity.id } );
		if ( response.data || response.hasOwnProperty( action ) ) {
			setModal( {
				size: 'xl',
				title: modalTitle,
				body: (
					<ExportModalContent data={ response[ action ] ?? response.data } />
				),
				buttonClose: t('Close'),
				buttonSave: null,
				handleSave: null,
			} );
		}
	}, [ entity, type, setModal, endpoint, action ] );

	const triggerProps = {
		onClick: handleTrigger,
	}

	return (
		<>
			{ typeof children === 'function' ? children( triggerProps ) : cloneElement( children, triggerProps ) }
			{ modal &&
				<Modal show={ ! isEmpty( modal ) } size={ modal.size ?? 'md' } onHide={ handleClose } centered scrollable>
					<Modal.Header closeButton>
						<Modal.Title>{ modal.title }</Modal.Title>
					</Modal.Header>
					{ modal.body &&
						<Modal.Body>{ modal.body }</Modal.Body>
					}
					<Modal.Footer>
						<Button variant="secondary" onClick={ handleClose }>
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
