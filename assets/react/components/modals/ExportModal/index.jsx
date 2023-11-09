import React, { useState, cloneElement, useCallback } from 'react';
import { Button, Modal, Spinner } from 'react-bootstrap';

import ExportModalContent from './ExportContent';
import ModalWrapper from '../ModelWrapper';

import { isEmpty } from "../../../utils/conditionals";
import { fetchPost } from "../../../utils/fetch";

export default function ExportModal( props ) {

	const {
		children,
		type,
		id,
		name,
		action, // @todo remove or use.
		endpoint = window.app.endpoints.entities[ type ] ?? window.app.baseUrl,
	} = props;

	const entity = {
		name: name ?? 'New',
		id: id ?? 'new',
	};

	const [ modal, setModal ] = useState( false );

	const handleClose = useCallback( () => {
		setModal( false )
	}, [] );

	const handleTrigger = ( e ) => {
		e.preventDefault;
		e.stopPropagation;
		openModal();
	};

	const openModal = async () => {
		let modalTitle = 'Export';

		// @todo Labels hook?
		modalTitle += ' ' + ( entity._class || window.app.labels[ type ] || type );

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
			buttonClose: 'Cancel',
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
				buttonClose: 'Close',
				buttonSave: null,
				handleSave: null,
			} );
		}
	}

	const triggerProps = {
		onClick: handleTrigger,
	}

	return (
		<>
			{ typeof children === 'function' ? children( triggerProps ) : cloneElement( children, triggerProps ) }
			{ modal &&
				<ModalWrapper>
					<Modal show={ ! isEmpty( modal ) } size={ modal.size ?? 'md' } onHide={ handleClose } centered scrollable>
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
