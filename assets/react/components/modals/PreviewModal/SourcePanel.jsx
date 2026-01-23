import React, { useContext } from 'react';
import { useTranslation } from 'react-i18next';
import { Spinner, Stack, Tab, Tabs } from 'react-bootstrap';
import Button from '../../partials/Button';

import { PreviewModalContext } from './index';
import ContextScope from '../../services/ContextScope';
import Icon from '../../partials/Icon';
import FieldContainer from '../../form/Field/Container';
import Code from '../../fields/Code';
import Params from '../../fields/Params';
import Toggle from '../../fields/Toggle';
import { isEmpty } from '../../../utils/conditions';

export default function SourcePanel( props ) {
	const { t } = useTranslation();
	const previewModalContext = useContext( PreviewModalContext );

	const {
		context,
		loading,
	} = props;

	const request = previewModalContext.request;
	const previewData = previewModalContext.previewData;
	const sendData = previewModalContext.sendData;
	const previewRequestParams = previewModalContext.previewRequestParams;
	const previewRequestQuery = previewModalContext.previewRequestQuery;
	const variables = previewModalContext.variables;

	const configuredIcon = <Icon icon="gear-fill" className="position-absolute top-0 end-0 me-n2 mt-n2 fs-smaller"/>;

	return (
		<>

			<Tabs>
				{ context.scope &&
				  <Tab title={ t( 'Context' ) } key="context" eventKey="context">
					  <div className="pt-3">
						  <ContextScope
							  context={ context }
							  toolbar={
								  <>
									  <Stack direction="horizontal" gap={2} className="justify-content-center mt-2">
										  <Button disabled={ loading } onClick={ () => { request( { action: 'scope', mode: 'safe' } ) } }>
											  { 'scope-safe' === loading ?
												  <Spinner animation="grow" size="sm" className="me-2" />
												  :
												  <Icon icon="preview-scope" className="me-2" />
											  }
											  { t('Dry Fetch and Run (safe)') }
										  </Button>
										  <Button disabled={ loading } onClick={ () => { request( { action: 'scope', mode: 'live' } ) } } variant="outline-danger">
											  { 'scope-live' === loading ?
												  <Spinner animation="grow" size="sm" className="me-2" />
												  :
												  <Icon icon="preview-scope-live" className="me-2" />
											  }
											  { t('Fetch and Run') }
										  </Button>
									  </Stack>
									  <Toggle value={ sendData.current } onChange={ sendData.toggle } label={ t('Send data with context') } />
								  </>
							  }
						  />
					  </div>
				  </Tab>
				}
				<Tab
					title={
					<span className="d-block position-relative">
						{ t( 'Request' ) }
						{ ( ! isEmpty( previewRequestParams ) || ! isEmpty( previewRequestQuery ) ) && configuredIcon }
					</span>
					}
					key="request"
					eventKey="request"
				>
					<FieldContainer
						value={ previewRequestParams.current }
						/*collapsed={ isEmpty( previewRequestParams.current ) }*/
						label={ t( 'Request Params' ) }
						description={ t( 'Define request params to be used within preview.' ) }
					>
						<Params
							value={ previewRequestParams.current }
							onChange={ previewRequestParams.update }
						/>
					</FieldContainer>
					<FieldContainer
						value={ previewRequestQuery.current }
						/*collapsed={ isEmpty( previewRequestQuery.current ) }*/
						label={ t( 'Request Query' ) }
						description={ t( 'Define request query to be used within preview.' ) }
					>
						<Params
							value={ previewRequestQuery.current }
							onChange={ previewRequestQuery.update }
						/>
					</FieldContainer>
				</Tab>
				<Tab
					title={
						<span className="d-block position-relative">
							{ t( 'Variables' ) }
							{ ! isEmpty( variables.current ) && configuredIcon }
						</span>
					}
					key="variables"
					eventKey="variables"
				>
					<FieldContainer
						value={ variables.current }
						/*collapsed={ isEmpty( variables.current ) }*/
						label={ t( 'Variables' ) }
						description={ t( 'Define static variables to be used within preview.' ) }
					>
						<Params
							value={ variables.current }
							onChange={ variables.update }
						/>
					</FieldContainer>
				</Tab>
			</Tabs>
			<hr />
			<div className={ ( context.scope && ! sendData ) ? 'opacity-50' : '' }>
				<Code
					language="json"
					value={ previewData.current }
					onChange={ previewData.update }
				/>
			</div>
		</>
	)
}
