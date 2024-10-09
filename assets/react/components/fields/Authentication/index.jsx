import React, { useContext, useState } from 'react';
import { any, array, bool, func, object, oneOfType } from 'prop-types';
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
import { isEmpty, isFieldEditable, isObject } from '../../../utils/conditions';
import { deepClone } from '../../../utils/data';
import { FieldContext } from '../../../context/FieldsContext';

export default function Authentication( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );

	const {
		onChange,
		value = {},
	} = props;

	const config = { ...value };
	const [ selectedWebservice, setSelectedWebservice ] = useState( ( config._class ?? '' ) );
	const [ webserviceTypes ] = useWebservices( props.webserviceTypes, props.query ?? {} );

	const [ connectConfig, setConnectConfig ] = useState( config._connect ?? {} );
	const entity = useContext( EntityContext );

	let tags = useContext( TagsContext );

	if ( null === webserviceTypes ) {
		return <LoadingPlaceholder/>
	}

	const updateConnectConfig = ( connectConfig ) => {
		if ( ! editable ) { return; }
		setConnectConfig( connectConfig );

		config._connect = connectConfig;
		onChange( config );
	}

	const selectWebservice = ( type ) => {
		if ( ! editable ) { return; }
		setSelectedWebservice( type );

		config._class = type;
		onChange( config );
	}

	const updateWebservice = ( webservice ) => {
		if ( ! editable ) { return; }

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
			<SelectWebservice options={ webserviceTypes } onChange={ selectWebservice } value={ selectedWebservice } editable={ editable } />
			{ authFields &&
				<FieldContainer
					icon="authorization"
					label={ t( 'Authorization' ) }
					collapsed={ false }
					toolbar={
						<Connect
							entity={ entity }
							authConfig={ config }
							connectConfig={ connectConfig }
							fields={ connectFields }
							onChange={ updateConnectConfig }
							editable={ editable }
						/>
					}
				>
					<TagsContext.Provider value={ {
						...deepClone( tags ),
						...webserviceTypes[ selectedWebservice ].tags.auth ?? {}
					} }>
						<Fields fields={ authFields } value={ config } onChange={ updateWebservice } editable={ editable } />
					</TagsContext.Provider>
				</FieldContainer>
			}
		</Stack>
	);
}

Authentication.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	query: object,
	webserviceTypes: oneOfType( [ object, array ] ),
}

const Connect = ( props ) => {
	const { t } = useTranslation();

	const {
		name = '_connect',
		entity,
		authConfig,
		connectConfig,
		onChange,
		fields,
		editable,
	} = props;

	if ( ! fields ) {
		return;
	}

	const confirm = ( editable && isObject( fields ) && ! isEmpty( fields ) ) ? (
		<FieldContext.Provider value={ { name: name } }>
			<Fields fields={ fields } value={ connectConfig } onChange={ onChange } editable={ editable } />
		</FieldContext.Provider>
	) : false;

	return (
		<RequestModal
			type={ entity._entity.toLowerCase() }
			entity={ entity }
			size="lg"
			action="connect"
			title={ t('Connect' ) }
			// @todo Only pass webservice instead of full config?
			params={ { config: entity.config, authConfig: authConfig, connectConfig: connectConfig, id: 'entityId' } }
			confirm={ confirm }
		>
			<Button>{ t('Connect' ) }</Button>
		</RequestModal>
	);
}
