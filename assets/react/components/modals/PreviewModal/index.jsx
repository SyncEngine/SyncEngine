import React, { useState, cloneElement, useCallback } from 'react';
import { Button, Modal, Spinner, Tabs, Tab, Container, Row, Col, Stack } from 'react-bootstrap';

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
							<Container fluid>
								<Row>
									<Col>
										<Stack gap={3}>
											<p className="h6">Data</p>
											<Tabs activeKey={ source }>
												<Tab eventKey="manual" key="manual" title="Manual" onClick={ () => { setSource( 'manual' ) } }>
													<Code defaultValue={ localStorage.getItem( 'manual-test-code' ) } onChange={ ( value ) => {
														localStorage.setItem( 'manual-test-code', value );
													} } />
												</Tab>
												<Tab eventKey="context" key="context" title="Context" onClick={ () => { setSource( 'context' ) } }>
													Todo
												</Tab>
											</Tabs>
										</Stack>
									</Col>
									<Col>
										<Stack gap={3}>
											<p className="h6">Config</p>
											{ onSave && fields &&
												<Fields fields={ fields } value={ config } onChange={ ( input ) => setConfig( input ) } />
											}
											<Stack direction="horizontal" gap={2} className="justify-content-center"><Button onClick={ () => { request() } }>Run</Button></Stack>
										</Stack>
									</Col>
									<Col>
										<Stack gap={3}>
											<p className="h6">Result</p>
											{ modal.response }
										</Stack>
									</Col>
								</Row>
							</Container>
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
