import React from 'react';
import { any, bool, func, object, oneOfType, string } from 'prop-types';
import { useTranslation } from 'react-i18next';

import { Alert, Stack } from 'react-bootstrap';
import Fields from '../../form/Fields';
import useWebservices from '../../../hooks/useWebservices';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

export default function Webservice( props ) {
	const { t } = useTranslation();

	const [ webservices ] = useWebservices( null, {} );
	const webservice = ( webservices && props.webservice ) ? ( webservices[ props.webservice._class ?? props.webservice ] ?? null ) : null;

	if ( ! webservices || ! props.webservice ) {
		return <LoadingPlaceholder/>
	}

	if ( ! webservice ) {
		return <Alert variant="danger">{ t('Webservice not found') }</Alert>
	}

	if ( ! props.mode ) {
		return <Alert variant="warning">{ t('Please provide a webservice mode') }</Alert>
	}

	if ( ! webservice.hasOwnProperty( 'fields' ) ) {
		return <Alert variant="warning">{ t('Webservice has no fields') }</Alert>
	}

	if ( ! webservice.fields.hasOwnProperty( props.mode ) ) {
		return <Alert variant="warning">{ t('Webservice has no fields for "{{mode}}"', { mode: props.mode }) }</Alert>
	}

	return (
		<Stack gap={ 2 }>
			<Fields { ...props } fields={ webservice.fields[ props.mode ] } />
		</Stack>
	);
}

Webservice.propTypes = {
	onChange: func,
	value: any,
	default: any,
	editable: bool,
	disabled: bool,
	readonly: bool,
	webservice: oneOfType( [ string, object ] ),
}
