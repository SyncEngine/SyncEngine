import React, { useState, cloneElement, useCallback } from 'react';
import { Button, Modal, Spinner, Tabs, Tab } from "react-bootstrap";

import { ElementContext } from "../../../context/ElementContext";
import { isEmpty } from "../../../utils/conditionals";
import { fetchPost } from "../../../utils/fetch";
import { objectToMappable } from "../../../utils/data";
import { ucfirst } from "../../../utils/globals";

export default function RequestModal( props ) {

	const {
		children,
		confirm = true,
		type,
		title = 'Request',
		action,
		params,
		item,
		entity,
		element = React.useContext( ElementContext ),
		endpoint = window.app.endpoints.requests[ type ] ?? window.app.baseUrl,
	} = props;

	const [ modal, setModal ] = useState( false );

	const getTitle = () => {
		return title + ( entity ? entity.name ?? '' : '' );
	}

	const getData = () => {
		const data = {
			action: action,
		}

		for ( const key in params ) {
			if ( ! params.hasOwnProperty( key ) ) {
				continue;
			}
			switch ( params[ key ] ) {
				case 'item':
					data[ key ] = JSON.stringify( item );
					break;
				case 'element':
					data[ key ] = element.value;
					break;
				case 'entity':
					data[ key ] = entity ? JSON.stringify( entity ) : null;
					break;
				case 'entityId':
					if ( entity && entity.id ) {
						data[ key ] = entity.id;
					} else {
						// @todo Enhance this.
						data[ key ] = element.parentNode.parentNode.dataset.id;
					}
					break;
				default:
					data[ key ] = params[ key ];
					break;
			}
		}

		return data;
	}

	const handleClose = useCallback( () => {
		setModal( false )
	}, [] );
	const handleTrigger = ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		openModal();
	};

	const openModal = () => {

		const initRequestModal = () => {
			setModal( {
				title: getTitle(),
				body: (
					<Spinner animation="border" role="status">
						<span className="visually-hidden">Loading...</span>
					</Spinner>
				),
				buttonClose: 'Cancel',
				buttonSave: '',
				handleSave: null
			} );
			request( endpoint, getData() );
		};

		if ( true === confirm ) {
			setModal( {
				title: getTitle(),
				body: (
					"Send request?"
				),
				buttonClose: 'Cancel',
				buttonSave: 'Send',
				handleSave: initRequestModal,
			} );
		} else {
			initRequestModal();
		}
	}

	const request = async ( endpoint, data ) => {
		const response = await fetchPost( endpoint, data );
		if ( response ) {
			setModal( {
				size: 'xl',
				title: getTitle() + ': ' + ( response.success ? 'Success' : 'Error' ),
				body: (
					<>
						{ response.message ?? '' }
						{ response.data &&
							<Tabs>
								{
									objectToMappable( response.data, 'name', 'content', true ).map( tab => {
										return (
											<Tab eventKey={ tab.name } key={ tab.name } title={ ucfirst( tab.name ) }>
												<pre>{ JSON.stringify( tab.content, null, 2 ) }</pre>
											</Tab>
										)
									} )
								}
							</Tabs>
						}
					</>
				),
				buttonClose: 'Close',
				buttonSave: '',
			} );
		}
	}

	const triggerProps = {
		onClick: handleTrigger,
		onFocus: e => e.stopPropagation()
	}

	return (
		<>
			{ typeof children === 'function' ? children( triggerProps ) : cloneElement( children, triggerProps ) }
			{ modal &&
				<div
					onKeyDown={e => e.stopPropagation()}
					onClick={e => e.stopPropagation()}
					onFocus={e => e.stopPropagation()}
					onMouseOver={e => e.stopPropagation()}
				>
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
				</div>
			}
		</>
	);
}
