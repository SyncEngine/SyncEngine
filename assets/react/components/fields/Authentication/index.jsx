import React, { useContext, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Stack } from 'react-bootstrap';

import useWebservices from '../../../hooks/useWebservices';
import SelectWebservice from '../../form/SelectWebservice';

import { TagsContext } from '../../../context/TagsContext';
import { EntityContext } from '../../../context/EntityContext';

import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import Button from '../../partials/Button';
import RequestModal from '../../modals/RequestModal';

import FieldContainer from '../../form/Field/Container';
import Fields from '../../form/Fields';
import { isEmpty, isObject } from '../../../utils/conditions';
import { deepClone } from '../../../utils/data';

export default function Authentication( props ) {
	const { t } = useTranslation();

	const {
		onChange,
	} = props;

	const config = { ...props.value };
	const [ selectedWebservice, setSelectedWebservice ] = useState( ( config._class ?? '' ) );
	const [ webserviceTypes ] = useWebservices( props.webserviceTypes, props.query ?? {} );

	const [ connectConfig, setConnectConfig ] = useState( config._connect ?? {} );
	const entity = useContext( EntityContext );

	let tags = useContext( TagsContext );

	if ( null === webserviceTypes ) {
		return <LoadingPlaceholder/>
	}

	const updateConnectConfig = ( connectConfig ) => {
		setConnectConfig( connectConfig );

		config._connect = connectConfig;
		onChange( config );
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
				...webserviceTypes[ selectedWebservice ].fields[ type ] ?? null,
			}
		}
		return null;
	}

	const authFields = getWebserviceFields( 'auth' );
	const connectFields = getWebserviceFields( 'connect' );

	return (
		<Stack gap={2}>
			<SelectWebservice options={ webserviceTypes } onChange={ selectWebservice } value={ selectedWebservice } />
			{ authFields &&
				<FieldContainer
					label={ t( 'Authorization' ) }
					collapsed={ false }
					toolbar={ connectFields &&
						<RequestModal
							type={ entity._entity.toLowerCase() }
							entity={ entity }
							size="lg"
							action="connect"
							title={ t('Connect' ) }
							params={ { authConfig: config, connectConfig: connectConfig, id: 'entityId' } }
							confirm={ ( isObject( connectFields ) && ! isEmpty( connectFields ) ) ? <Fields fields={ connectFields } value={ connectConfig } onChange={ updateConnectConfig } /> : false }
						>
							<Button>{ t('Connect' ) }</Button>
						</RequestModal>
					}
				>
					<TagsContext.Provider value={ {
						...deepClone( tags ),
						...webserviceTypes[ selectedWebservice ].tags.auth ?? {}
					} }>
						<Fields fields={ authFields } value={ config } onChange={ updateWebservice } />
					</TagsContext.Provider>
				</FieldContainer>
			}
		</Stack>
	);
}
