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
		item,
		entity,
		element = React.useContext( ElementContext ),
		endpoint = window.app.endpoints.requests[ type ] ?? window.app.baseUrl,
	} = props;

	const [ modal, setModal ] = useState( false );

	const getTitle = () => {
		return title + ( entity ? entity.name ?? '' : '' );
	}

	const parseParams = ( params = props.params ?? {} ) => {
		if ( ! params.action && action ) {
			params.action = action;
		}

		console.log( params );

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

		const initRequestModal = ( params ) => {
			setModal( {
				title: getTitle(),
				body: (
					<Spinner animation="border" role="status">
						<span className="visually-hidden">Loading...</span>
					</Spinner>
				),
				buttonClose: 'Cancel',
				buttonSave: '',
				handleSave: null,
			} );
			request( endpoint, parseParams( params ) );
		};

		if ( confirm ) {
			if ( props.actions ) {

				const actions = objectToMappable( props.actions, 'action' ).map( ( action ) => {
					return <Button
						variant={ action.variant ?? 'primary' }
						onClick={ () => { initRequestModal( { ...action.params, action: action.action } ) } }
					>
						{ action.title }
					</Button>
				} );

				setModal( {
					title: getTitle(),
					body: actions,
					buttonClose: 'Cancel',
					buttonSave: '',
					handleSave: null,
				} );
			} else {
				setModal( {
					title: getTitle(),
					body: (
						"Send request?"
					),
					buttonClose: 'Cancel',
					buttonSave: 'Send',
					handleSave: () => initRequestModal(),
				} );
			}
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
