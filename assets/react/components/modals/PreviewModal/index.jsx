import React, { cloneElement, createContext, useCallback, useContext, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Card, Col, Spinner, Stack, Tab, Tabs } from 'react-bootstrap';

import Fields from '../../form/Fields';
import Modal from '../Modal';

import ResponseTabs from '../../services/ResponseTabs';

import useSettings from '../../../hooks/useSettings';
import useGlobal from '../../../hooks/useGlobal';
import useToggle from '../../../hooks/useToggle';

import { ParentContext } from '../../../context/ParentContext';
import { isEmpty, isFunction } from '../../../utils/conditions';
import { fetchPost } from '../../../utils/fetch';
import { deepClone } from '../../../utils/data';
import Icon from '../../partials/Icon';
import { debug } from '../../../utils/globals';
import { suppress } from '../../../utils/events';
import SourcePanel from './SourcePanel';

export const PreviewModalContext = createContext();

function getConfig( item ) {
	let config = item;
	if ( 'function' === typeof config ) {
		config = config();
	}
	return ( 'object' === config ) ? deepClone( config ) : {};
}

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

	const [ modal, setModal ] = useState( false );
	const [ config, setConfig ] = useState( getConfig( item ) );
	const [ previewData, updatePreviewData ] = useSettings( 'local', 'preview', 'data', null, false );
	const [ variables, updateVariables ] = useSettings( 'local', 'preview', 'variables', null, true );
	const [ loading, setLoading ] = useState( '' );
	const [ changed, setChanged ] = useState( false );
	const [ previewRequestParams, updatePreviewRequestParams ] = useSettings( 'local', 'preview', 'requestParams', null, true );
	const [ previewRequestQuery, updatePreviewRequestQuery ] = useSettings( 'local', 'preview', 'requestQuery', null, true );
	const [ sendData, toggleSendData ] = useToggle( false );

	const [ showSourcePanel, toggleShowSourcePanel ] = useToggle( true );
	const [ showResultPanel, toggleShowResultPanel ] = useToggle( true );
	const [ largeConfig, toggleLargeConfig ] = useToggle( false );

	const context = props.context || useContext( ParentContext );
	const scope = props.scope ?? context?.scope;
	const isLoading = ! isEmpty( loading );

	const getTitle = () => {
		let postfix = ' ' +  ( entity ? entity.name ?? '' : item.name ?? '' );
		return ( 'function' === typeof title ) ? title( item ) + postfix : title + postfix;
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
		try {
			updatePreviewData( data );
		} catch ( err ) {
			// Most likely to much data.
			alert( 'Unable to update preview data with response.' );
			debug( err );
		}
	}, [ updatePreviewData ] );

	const parseParams = ( params = props.params ?? {} ) => {
		if ( props.params ) {
			params = { ...props.params, ...params };
		}

		if ( ! params.action ) {
			params.action = 'preview';
		}

		let passData = true;

		if ( 'scope' === params.action ) {
			params.scope = scope;
			passData = sendData;
		}

		if ( passData ) {
			// @todo different sources.
			params.data = getPreviewData();
		}

		params.requestParams = previewRequestParams;
		params.requestQuery = previewRequestQuery;
		params.variables = variables;

		params.type = type;
		params.config = config;
		params.ref = config._ref ?? entity.ref ?? '';

		if ( isFunction( props.onParse ) ) {
			params = props.onParse( params );
		}

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

			if ( response.source && ! sendData ) {
				setPreviewData( response.source );
			}

			setModal( {
				...modal,
				response: (
					<>
						{ /*response.success ? 'Success' : 'Error'*/ }
						{ response.message && <div dangerouslySetInnerHTML={ { __html: response.message } }></div> }
						{ response.error && <div dangerouslySetInnerHTML={ { __html: response.error } }></div> }
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
	const handleUpdate = () => {
		setChanged( false );
		onSave( config );
	};
	const handleUpdateClose = () => {
		onSave( config );
		handleClose();
	};
	const handleUpdateScope = () => {
		alert( 'Todo' );
	};
	const handleTrigger = ( e ) => {
		suppress( e );
		openModal();
	};
	const triggerProps = {
		onClick: handleTrigger,
		onFocus: e => e.stopPropagation()
	}

	const previewModalContext = {
		scope: scope,
		request: request,
		previewData: {
			current: getPreviewData( true ),
			update: setPreviewData
		},
		sendData: {
			current: sendData,
			toggle: toggleSendData
		},
		previewRequestParams: {
			current: previewRequestParams,
			update: updatePreviewRequestParams
		},
		previewRequestQuery: {
			current: previewRequestQuery,
			update: updatePreviewRequestQuery
		},
		variables: {
			current: variables,
			update: updateVariables
		},
	};

	return (
		<PreviewModalContext.Provider value={ previewModalContext }>
			{ typeof children === 'function' ? children( triggerProps ) : cloneElement( children, triggerProps ) }
			{ modal &&
				<Modal show={ ! isEmpty( modal ) } onHide={ handleClose } dialogClassName="p-5" fullscreen centered scrollable>
					<Modal.Header className={ 'bg-pink' } closeButton onHide={ handleClose }>
						<Modal.Title>
							<Icon icon={ props.icon ?? "preview" } className="me-3" />
							{ modal.title }
						</Modal.Title>
					</Modal.Header>
					<Modal.Body className={ loading && "opacity-75" }>
						<Stack direction="horizontal" gap={3} className="h-100 mh-100 align-items-stretch">
							<Col className={ "d-flex overflow-x-auto" + ( ! showSourcePanel ? ' col-auto' : '' ) }>
								<Stack gap={3} className="h-100 mh-100 mw-100">
									<p className="h6 d-flex justify-content-between gap-2">
										{ t('Data') }
										<Icon onClick={ toggleShowSourcePanel } icon={ showSourcePanel ? 'visible' : 'hidden' } className="icon-btn" />
									</p>
									{ showSourcePanel &&
										<div className="flex-grow-1 flex-basis-0 d-flex flex-column overflow-y-auto">
											<SourcePanel loading={ loading } />
										</div>
									}
								</Stack>
							</Col>
							<Col className={ "d-flex overflow-x-auto my-n3 bg-body-tertiary" + ( largeConfig ? ' col-8' : '' ) }>
								<Stack gap={3} className="h-100 mh-100 mw-100 p-3">
									<p className="h6 d-flex justify-content-between gap-2">
										{ t( 'Config' ) }
										<Icon onClick={ toggleLargeConfig } icon={ largeConfig ? 'size-contract-x' : 'size-expand-x' } className="icon-btn" />
									</p>
									{ ( onSave && fields ) &&
										<Card className="bg-body border-0 overflow-y-auto">
											<Card.Body className="border p-3">
												{ React.isValidElement( fields ) ? fields :
													<Fields name="_preview" fields={ fields } value={ config } onChange={ ( input ) => { setConfig( input ); setChanged( true ) } } />
												}
											</Card.Body>
										</Card>
									}

									<Stack direction="horizontal" gap={2} className="justify-content-center">
										<Button disabled={ isLoading } onClick={ () => { request( { mode: 'safe' } ) } }>
											{ 'safe' === loading ?
											    <Spinner animation="grow" size="sm" className="me-2" />
												:
												<Icon icon="preview" className="me-2" />
											}
											{ t('Dry Run (safe)') }
										</Button>
										<Button disabled={ isLoading } onClick={ () => { request( { mode: 'live' } ) } } variant="outline-danger">
											{ 'live' === loading ?
												<Spinner animation="grow" size="sm" className="me-2" />
												:
												<Icon icon="preview-live" className="me-2" />
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
										<Icon onClick={ toggleShowResultPanel } icon={ showResultPanel ? 'visible' : 'hidden' } className="icon-btn" />
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
						  <Button disabled={ isLoading } variant="outline-secondary" onClick={ handleClose }>{ t('Close') }</Button>
							{ changed &&
							    <>
									<Button disabled={ isLoading } variant="outline-primary" onClick={ handleUpdate } title={ t( 'Save and continue' ) }>
										<Icon icon="set" className="me-2" />
										{ t( 'Set' ) }
									</Button>
									<Button disabled={ isLoading } variant="primary" onClick={ handleUpdateClose } title={ t( 'Update and close' ) }>
										<Icon icon="save" className="me-2" />
										{ t('Set and close') }
									</Button>
									{ scope &&
										<Button disabled={ isLoading } variant="outline-danger" onClick={ handleUpdateScope } title={ t( 'Update full scope and close' ) }>
											<Icon icon="update" className="me-2" />
											{ t('Save and close') }
										</Button>
									}
							    </>
							}
						</Modal.Footer>
					}
				</Modal>
			}
		</PreviewModalContext.Provider>
	);
}
