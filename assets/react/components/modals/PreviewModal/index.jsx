import React, { useState, cloneElement, useCallback, useContext } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Modal, Spinner, Tabs, Tab, Col, Stack, Card } from 'react-bootstrap';

import Code from '../../fields/Code';
import Fields from '../../form/Fields';
import ModalWrapper from '../ModelWrapper';

import { ParentContext } from '../../../context/ParentContext';
import { isEmpty } from "../../../utils/conditionals";
import { fetchPost } from "../../../utils/fetch";
import { objectToMappable } from "../../../utils/data";
import { ucfirst } from "../../../utils/globals";
import ContextScope from '../../services/ContextScope';

export default function PreviewModal( props ) {
	const { t } = useTranslation();

	const {
		children,
		type,
		title = t('Preview'),
		item = props.config ?? {},
		entity,
		endpoint = window.app.endpoints.requests.preview,
		fields,
		onSave,
	} = props;

	const [ modal, setModal ] = useState( false );
	const [ config, setConfig ] = useState( item );

	const context = useContext( ParentContext );

	const getTitle = () => {
		return title + ' ' +  ( entity ? entity.name ?? '' : item.name ?? '' );
	}

	const getPreviewSourceData = useCallback( ( stringify = true ) => {
		let data = localStorage.getItem( 'preview-source-data' );
		if ( stringify && 'object' === typeof data ) {
			data = JSON.stringify( data, null, 2 );
		}
		return data;
	}, [] );

	const setPreviewSourceData = useCallback( ( data, stringify = true ) => {
		if ( stringify && 'object' === typeof data ) {
			data = JSON.stringify( data, null, 2 );
		}
		localStorage.setItem( 'preview-source-data', data );
	}, [] );

	const parseParams = ( params = props.params ?? {} ) => {
		if ( props.params ) {
			params = { ...props.params, params };
		}

		if ( ! params.action ) {
			params.action = 'preview';
		}

		if ( 'scope' === params.action ) {
			params.scope = context.scope;
		}

		params.type = type;
		params.config = config;
		params.ref = config._ref ?? entity.ref ?? '';

		// @todo different sources.
		params.data = getPreviewSourceData();

		return params;
	}

	const openModal = () => {
		if ( 'function' === typeof item ) {
			// Reload config.
			setConfig( item );
		}

		setModal( {
			title: getTitle(),
		} );
	}

	const request = async( params ) => {
		setModal( {
			...modal,
			response: (
				<Spinner animation="border" role="status">
					<span className="visually-hidden">Loading...</span>
				</Spinner>
			),
		} );

		const response = await fetchPost( endpoint, parseParams( params ) );
		if ( response ) {

			if ( response.source ) {
				setPreviewSourceData( response.source );
			}

			setModal( {
				...modal,
				response: (
					<>
						{ /*response.success ? 'Success' : 'Error'*/ }
						{ response.message ?? '' }
						{ response.data &&
							<Tabs>
								{
									objectToMappable( response.data, 'name', 'content', true ).map( tab => {
										return (
											<Tab eventKey={ tab.name } key={ tab.name } title={ ucfirst( tab.name ) }>
												<pre className="bg-body-tertiary p-3">{ ( 'object' === typeof tab.content ) ? JSON.stringify( tab.content, null, 2 ) : tab.content }</pre>
											</Tab>
										)
									} )
								}
							</Tabs>
						}
					</>
				),
			} );
		} else {
			setModal( { ...modal, response: null } );
		}
	}

	const handleClose = useCallback( () => {
		setModal( false );
	}, [ setModal ] );
	const handleSave = () => {
		onSave( config );
		handleClose();
	};
	const handleTrigger = ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
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
				<ModalWrapper>
					<Modal show={ ! isEmpty( modal ) } onHide={ handleClose } dialogClassName="p-5" fullscreen centered scrollable>
						<Modal.Header closeButton onHide={ handleClose }>
							<Modal.Title>{ modal.title }</Modal.Title>
						</Modal.Header>
						<Modal.Body>
							<Stack direction="horizontal" gap={3} className="h-100 mh-100 align-items-stretch">
								<Col className="d-flex overflow-x-auto">
									<Stack gap={3} className="h-100 mh-100 mw-100">
										<p className="h6">{ t('Data') }</p>
										<div className="flex-grow-1 flex-basis-0 d-flex flex-column overflow-y-auto">
											{ context.scope &&
												<div>
													<ContextScope
														context={ context }
														toolbar={
															<Stack direction="horizontal" gap={2} className="justify-content-center mt-2">
																<Button onClick={ () => { request( { action: 'scope', mode: 'safe' } ) } }>{ t('Dry Fetch and Run (safe)') }</Button>
																<Button onClick={ () => { request( { action: 'scope', mode: 'live' } ) } } variant="danger">{ t('Fetch and Run') }</Button>
															</Stack>
														}
													/>
												</div>
											}
											<hr/>
											<Code
												value={ getPreviewSourceData( true ) }
												onChange={ ( value ) => { setPreviewSourceData( value ); } }
											/>
										</div>
									</Stack>
								</Col>
								<Col className="d-flex overflow-x-auto p-3 my-n3 bg-body-tertiary">
									<Stack gap={3} className="h-100 mh-100 mw-100">
										<p className="h6">{ t('Config') }</p>
										{ onSave && fields &&
											<Card className="bg-body border-0 overflow-y-auto">
												<Card.Body className="border p-3">
													<Fields fields={ fields } value={ config } onChange={ ( input ) => setConfig( input ) } />
												</Card.Body>
											</Card>
										}

										<Stack direction="horizontal" gap={2} className="justify-content-center">
											<Button onClick={ () => { request( { mode: 'safe' } ) } }>{ t('Dry Run (safe)') }</Button>
											<Button onClick={ () => { request( { mode: 'live' } ) } } variant="danger">{ t('Run') }</Button>
										</Stack>
									</Stack>
								</Col>
								<Col className="d-flex overflow-x-auto">
									<Stack gap={3} className="h-100 mh-100 mw-100">
										<p className="h6">{ t('Result') }</p>
										<div className="flex-grow-1 flex-basis-0 d-flex flex-column overflow-y-auto">
											{ modal.response }
										</div>
									</Stack>
								</Col>
						</Stack>
						</Modal.Body>
						{ ( onSave && fields ) &&
							<Modal.Footer>
								<Button variant="secondary" onClick={ handleClose }>{ t('Close') }</Button>
								<Button variant="primary" onClick={ handleSave }>{ t('Save') }</Button>
							</Modal.Footer>
						}
					</Modal>
				</ModalWrapper>
			}
		</>
	);
}
