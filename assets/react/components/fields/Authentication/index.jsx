import React, { useContext, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Stack, Tab, Tabs } from 'react-bootstrap';

import useWebservices from '../../../hooks/useWebservices';

import Fields from '../../form/Fields';
import SelectWebservice from '../../form/SelectWebservice';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import { TagsContext } from '../../../context/TagsContext';
import { deepClone } from '../../../utils/data';
import RequestModal from '../../modals/RequestModal';
import { EntityContext } from '../../../context/EntityContext';
import Button from '../../partials/Button';

export default function Authentication( props ) {
	const { t } = useTranslation();

	const {
		onChange,
	} = props;

	const config = { ...props.value };
	const [ selectedWebservice, setSelectedWebservice ] = useState( ( config._class ?? '' ) );
	const [ webserviceTypes ] = useWebservices( props.webserviceTypes, props.query ?? {} );

	const [ connectConfig, setTestConfig ] = useState( {} );
	const entity = useContext( EntityContext );

	let tags = useContext( TagsContext );

	if ( null === webserviceTypes ) {
		return <LoadingPlaceholder/>
	}

	const selectWebservice = ( type ) => {
		setSelectedWebservice( type );

		config._class = type;
		onChange( config );
	}

	const updateWebservice = ( webservice ) => {
		if ( selectedWebservice ) {
			webservice._class = selectedWebservice;
		}
		onChange( webservice );
	}

	const getWebserviceFields = ( type ) => {
		if ( webserviceTypes[ selectedWebservice ] ) {
			return {
				...webserviceTypes[ selectedWebservice ].fields[ type ] ?? {},
			}
		}
		return null;
	}

	const authFields = getWebserviceFields( 'auth' );
	const connectFields = getWebserviceFields( 'connect' );

	return (
		<Stack gap={2} className="mt-2">
			<SelectWebservice options={ webserviceTypes } onChange={ selectWebservice } value={ selectedWebservice } />
			{ authFields &&
				<Stack gap={0}>
					<Tabs>
						<Tab eventKey="auth" title={ t('Authorization' ) } className="p-3 border bg-body">
							<TagsContext.Provider value={ {
								...deepClone( tags ),
								...webserviceTypes[ selectedWebservice ].tags.auth ?? {}
							} }>
								<Fields fields={ authFields } value={ config } onChange={ updateWebservice } />
							</TagsContext.Provider>
						</Tab>
						{ false !== connectFields &&
							<Tab eventKey="connect" title={ t('Connect' ) } className="p-3 border bg-body">
								{ 'object' === typeof connectFields &&
									<Fields fields={ connectFields } value={ connectConfig } onChange={ setTestConfig } />
								}
								<RequestModal
									type={ entity._entity.toLowerCase() }
									entity={ entity }
									action="connect"
									title={ t('Connect' ) }
									params={ { authConfig: config, connectConfig: connectConfig, id: 'entityId' } }
								>
									<Button>{ t('Connect' ) }</Button>
								</RequestModal>
							</Tab>
						}
					</Tabs>
				</Stack>
			}
		</Stack>
	);
}
