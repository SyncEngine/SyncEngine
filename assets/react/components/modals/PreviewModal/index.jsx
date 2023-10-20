import React, { useState, cloneElement, useCallback } from 'react';
import { Button, Modal, Spinner, Tabs, Tab, Col, Stack, Nav, Card } from 'react-bootstrap';

import { isEmpty } from "../../../utils/conditionals";
import { fetchPost } from "../../../utils/fetch";
import { objectToMappable } from "../../../utils/data";
import { ucfirst } from "../../../utils/globals";
import Code from '../../fields/Code';
import Fields from '../../form/Fields';

export default function PreviewModal( props ) {

	const {
		children,
		type,
		title = 'Preview',
		item = props.config ?? {},
		entity,
		endpoint = window.app.endpoints.requests[ type ] ?? window.app.baseUrl,
		fields,
		onSave,
	} = props;

	const [ modal, setModal ] = useState( false );
	const [ source, setSource ] = useState( 'manual' );
	const [ config, setConfig ] = useState( item );

	const getTitle = () => {
		return title + ' ' +  ( entity ? entity.name ?? '' : item.name ?? '' );
	}

	const parseParams = ( params = props.params ?? {} ) => {
		if ( props.params ) {
			params = { ...props.params, params };
		}

		if ( ! params.action ) {
			params.action = 'test';
		}

		params.source = source;

		// @todo different sources.
		params.data = localStorage.getItem( 'manual-test-code' );

		return params;
	}

	const openModal = () => {
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
				<div
					onKeyDown={e => e.stopPropagation()}
					onClick={e => e.stopPropagation()}
					onFocus={e => e.stopPropagation()}
					onMouseOver={e => e.stopPropagation()}
				>
					<Modal show={ ! isEmpty( modal ) } onHide={ handleClose } dialogClassName="p-5" fullscreen centered scrollable>
						<Modal.Header closeButton onHide={ handleClose }>
							<Modal.Title>{ modal.title }</Modal.Title>
						</Modal.Header>
						<Modal.Body>
							<Stack direction="horizontal" gap={3} className="h-100 mh-100 align-items-stretch">
								<Col className="d-flex">
									<Stack gap={3} className="h-100 mh-100">
										<p className="h6">Data</p>
										<div className="flex-grow-1 flex-basis-0 d-flex flex-column">
											<Tab.Container defaultActiveKey={ source }>
												<Nav variant="tabs" defaultActiveKey="/home"  onSelect={ ( key ) => { setSource( key ) } }>
													<Nav.Item>
														<Nav.Link eventKey="manual">Manual</Nav.Link>
													</Nav.Item>
													<Nav.Item>
														<Nav.Link eventKey="context">Context</Nav.Link>
													</Nav.Item>
												</Nav>
												<Tab.Content className="flex-grow-1 flex-basis-0 overflow-auto">
													<Tab.Pane eventKey="manual">
														<Code
															defaultValue={ localStorage.getItem( 'manual-test-code' ) }
															onChange={ ( value ) => { localStorage.setItem( 'manual-test-code', value ); } }
														/>
													</Tab.Pane>
													<Tab.Pane eventKey="context">
														Todo:
														Create context selector (Automation, Flow, Step) and make sure the options reflect the other options.
														For example, if a flow is selected, you can only select Automations and Steps that are related to this Flow.
													</Tab.Pane>
												</Tab.Content>
											</Tab.Container>
										</div>
									</Stack>
								</Col>
								<Col className="d-flex">
									<Stack gap={3} className="h-100 mh-100">
										<p className="h6">Config</p>
										{ onSave && fields &&
											<Card className="bg-body border-0">
												<Card.Body className="border p-3">
													<Fields fields={ fields } value={ config } onChange={ ( input ) => setConfig( input ) } />
												</Card.Body>
											</Card>
										}

										{ 'manual' === source ?
											<Stack direction="horizontal" gap={2} className="justify-content-center"><Button onClick={ () => { request() } }>Run</Button></Stack>
											:
											<Stack direction="horizontal" gap={2} className="justify-content-center">
												<Button onClick={ () => { request( { mode: 'preview' } ) } }>Dry Run (safe)</Button>
												<Button onClick={ () => { request( { mode: 'execute' } ) } } variant="danger">Run production</Button>
											</Stack>
										}
									</Stack>
								</Col>
								<Col className="d-flex">
									<Stack gap={3} className="h-100 mh-100">
										<p className="h6">Result</p>
										{ modal.response }
									</Stack>
								</Col>
						</Stack>
						</Modal.Body>
						{ ( onSave && fields ) &&
							<Modal.Footer>
								<Button variant="secondary" onClick={ handleClose }>Close</Button>
								<Button variant="primary" onClick={ handleSave }>Save</Button>
							</Modal.Footer>
						}
					</Modal>
				</div>
			}
		</>
	);
}
