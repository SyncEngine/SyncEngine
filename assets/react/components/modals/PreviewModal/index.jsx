import React, { useState, cloneElement, useCallback, useContext, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Spinner, Col, Stack, Card } from 'react-bootstrap';

import Code from '../../fields/Code';
import Fields from '../../form/Fields';
import Modal from '../Modal';

import ResponseTabs from '../ResponseTabs';

import useStorage from '../../../hooks/useStorage';
import useGlobal from '../../../hooks/useGlobal';
import useToggle from '../../../hooks/useToggle';

import ContextScope from '../../services/ContextScope';
import { ParentContext } from '../../../context/ParentContext';
import { isEmpty } from "../../../utils/conditions";
import { fetchPost } from "../../../utils/fetch";

export default function PreviewModal( props ) {
	const { t } = useTranslation();
	const app = useGlobal();

	const {
		children,
		type,
		title = t('Preview'),
		item = props.config ?? {},
		entity,
		endpoint = app.endpoints.requests.preview,
		fields,
		onSave,
	} = props;

	const getConfig = useCallback( ( item ) => {
		let config = item;
		if ( 'function' === typeof config ) {
			config = config();
		}
		return ( 'object' === config ) ? structuredClone( config ) : {};
	}, [] );

	const [ modal, setModal ] = useState( false );
	const [ config, setConfig ] = useState( getConfig( item ) );
	const [ previewData, updatePreviewData ] = useStorage( 'local', 'preview', 'data', null, false );
	const [ loading, setLoading ] = useState( '' );
	const [ changed, setChanged ] = useState( false );
	//const [ previewRequest, updatePreviewRequest ] = useStorage( 'local', 'preview', 'request', null, false )

	const [ showSourcePanel, toggleShowSourcePanel ] = useToggle( true );
	const [ showResultPanel, toggleShowResultPanel ] = useToggle( true );
	const [ largeConfig, toggleLargeConfig ] = useToggle( false );

	const context = useContext( ParentContext );

	const getTitle = () => {
		return title + ' ' +  ( entity ? entity.name ?? '' : item.name ?? '' );
	}

	const getPreviewData = useCallback( ( stringify = true ) => {
		let data = previewData;
		if ( stringify && 'object' === typeof data ) {
			data = JSON.stringify( data, null, 2 );
		}
		return data;
	}, [ previewData ] );

	const setPreviewData = useCallback( ( data, stringify = true ) => {
		if ( stringify && 'object' === typeof data ) {
			data = JSON.stringify( data, null, 2 );
		}
		updatePreviewData( data );
	}, [ updatePreviewData ] );

	const parseParams = ( params = props.params ?? {} ) => {
		if ( props.params ) {
			params = { ...props.params, params };
		}

		if ( ! params.action ) {
			params.action = 'preview';
		}

		if ( 'scope' === params.action ) {
			params.scope = context.scope;
		} else {
			// @todo different sources.
			params.data = getPreviewData();
		}

		params.type = type;
		params.config = config;
		params.ref = config._ref ?? entity.ref ?? '';

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
		setLoading( ( params.action ? params.action + '-' : '' ) + params.mode );
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
				setPreviewData( response.source );
			}

			setModal( {
				...modal,
				response: (
					<>
						{ /*response.success ? 'Success' : 'Error'*/ }
						{ response.message ?? '' }
						{ response.data &&
							<ResponseTabs data={ response.data } contained />
						}
					</>
				),
			} );
		} else {
			setModal( { ...modal, response: null } );
		}

		setLoading( false );
	}

	const handleClose = useCallback( () => {
		setModal( false );
	}, [ setModal ] );
	const handleSave = () => {
		setChanged( false );
		onSave( config );
	};
	const handleUpdate = () => {
		onSave( config );
		handleClose();
	};
	const handleUpdateScope = () => {
		alert( 'Todo' );
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
				<Modal show={ ! isEmpty( modal ) } onHide={ handleClose } dialogClassName="p-5" fullscreen centered scrollable>
					<Modal.Header closeButton onHide={ handleClose }>
						<Modal.Title>
							<span className="bi bi-play-circle me-3"></span>
							{ modal.title }
						</Modal.Title>
					</Modal.Header>
					<Modal.Body className={ loading && "opacity-75" }>
						<Stack direction="horizontal" gap={3} className="h-100 mh-100 align-items-stretch">
							<Col className={ "d-flex overflow-x-auto" + ( ! showSourcePanel ? ' col-auto' : '' ) }>
								<Stack gap={3} className="h-100 mh-100 mw-100">
									<p className="h6 d-flex justify-content-between gap-2">
										{ t('Data') }
										<span onClick={ toggleShowSourcePanel } className={ "icon-btn bi bi-" + ( showSourcePanel ? 'eye-fill' : 'eye-slash' ) } />
									</p>
									{ showSourcePanel &&
										<div className="flex-grow-1 flex-basis-0 d-flex flex-column overflow-y-auto">
											{ context.scope &&
												<div>
													<ContextScope
														context={ context }
														toolbar={
															<Stack direction="horizontal" gap={2} className="justify-content-center mt-2">
																<Button disabled={ loading } onClick={ () => { request( { action: 'scope', mode: 'safe' } ) } }>
																	{ 'scope-safe' === loading ?
																		<Spinner animation="grow" size="sm" className="me-2"/>
																		:
																		<span className="bi bi-skip-forward-circle me-2"/>
																	}
																	{ t('Dry Fetch and Run (safe)') }
																</Button>
																<Button disabled={ loading } onClick={ () => { request( { action: 'scope', mode: 'live' } ) } } variant="outline-danger">
																	{ 'scope-live' === loading ?
																		<Spinner animation="grow" size="sm" className="me-2"/>
																		:
																		<span className="bi bi-skip-forward-circle-fill me-2"/>
																	}
																	{ t('Fetch and Run') }
																</Button>
															</Stack>
														}
													/>
												</div>
											}
											<hr/>
											<Code
												language="json"
												value={ getPreviewData( true ) }
												onChange={ ( value ) => { setPreviewData( value ); } }
											/>
										</div>
									}
								</Stack>
							</Col>
							<Col className={ "d-flex overflow-x-auto my-n3 bg-body-tertiary" + ( largeConfig ? ' col-8' : '' ) }>
								<Stack gap={3} className="h-100 mh-100 mw-100 p-3">
									<p className="h6 d-flex justify-content-between gap-2">
										{ t( 'Config' ) }
										<span onClick={ toggleLargeConfig } className={ 'icon-btn bi bi-' + ( largeConfig ? 'arrows-collapse-vertical' : 'arrows-expand-vertical' ) }/>
									</p>
									{ (
									  onSave && fields
									  ) &&
									  <Card className="bg-body border-0 overflow-y-auto">
										  <Card.Body className="border p-3">
												<Fields fields={ fields } value={ config } onChange={ ( input ) => { setConfig( input ); setChanged( true ) } } />
											</Card.Body>
										</Card>
									}

									<Stack direction="horizontal" gap={2} className="justify-content-center">
										<Button disabled={ loading } onClick={ () => { request( { mode: 'safe' } ) } }>
											{ 'safe' === loading ?
											    <Spinner animation="grow" size="sm" className="me-2"/>
												:
												<span className="bi bi-play-circle me-2"/>
											}
											{ t('Dry Run (safe)') }
										</Button>
										<Button disabled={ loading } onClick={ () => { request( { mode: 'live' } ) } } variant="outline-danger">
											{ 'live' === loading ?
												<Spinner animation="grow" size="sm" className="me-2"/>
												:
												<span className="bi bi-play-circle-fill me-2"/>
											}
											{ t('Run') }
										</Button>
									</Stack>
								</Stack>
							</Col>
							<Col className={ "d-flex overflow-x-auto" + ( ! showResultPanel ? ' col-auto' : '' ) }>
								<Stack gap={3} className="h-100 mh-100 mw-100">
									<p className="h6 d-flex justify-content-between gap-2">
										{ t('Result') }
										<span onClick={ toggleShowResultPanel } className={ "icon-btn bi bi-" + ( showResultPanel ? 'eye-fill' : 'eye-slash' ) } />
									</p>
									{ showResultPanel &&
									    <div className="flex-grow-1 flex-basis-0 d-flex flex-column overflow-y-auto">
										    { modal.response }
									    </div>
									}
								</Stack>
							</Col>
						</Stack>
					</Modal.Body>
					{ (
					  onSave && fields
					  ) &&
					  <Modal.Footer>
						  <Button disabled={ loading } variant="outline-secondary" onClick={ handleClose }>{ t('Close') }</Button>
							{ changed &&
							    <>
									<Button disabled={ loading } variant="outline-primary" onClick={ handleSave } title={ t( 'Save and continue' ) }>
										<span className="bi bi-save me-2"/>
										{ t( 'Save' ) }
									</Button>
									<Button disabled={ loading } variant="primary" onClick={ handleUpdate } title={ t( 'Update and close' ) }>
										<span className="bi bi-check-square me-2" />
										{ t('Update') }
									</Button>
									{ context.scope &&
										<Button disabled={ loading } variant="outline-danger" onClick={ handleUpdateScope } title={ t( 'Update full scope and close' ) }>
											<span className="bi bi-pencil-square me-2" />
											{ t('Update scope') }
										</Button>
									}
							    </>
							}
						</Modal.Footer>
					}
				</Modal>
			}
		</>
	);
}
